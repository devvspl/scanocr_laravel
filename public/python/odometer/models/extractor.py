"""
EasyOCR Mileage Extractor.
Extracts numeric reading from cropped odometer region.
"""
import re
import cv2
import numpy as np


class MileageExtractor:
    """EasyOCR-based mileage reading extraction."""

    def __init__(self, languages=None, gpu=False):
        self.reader = None
        self.languages = languages or ['en']
        self.gpu = gpu
        self._loaded = False

    def _load(self):
        """Lazy load EasyOCR reader."""
        if self._loaded:
            return
        import easyocr
        self.reader = easyocr.Reader(self.languages, gpu=self.gpu, verbose=False)
        self._loaded = True

    def preprocess(self, image: np.ndarray, odo_type: str = 'digital') -> np.ndarray:
        """Preprocess image for better OCR accuracy."""
        if len(image.shape) == 3:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        else:
            gray = image

        # Upscale for better digit recognition
        h, w = gray.shape
        scale = 3 if odo_type == 'digital' else 4
        gray = cv2.resize(gray, (w * scale, h * scale), interpolation=cv2.INTER_CUBIC)

        # Invert if dark background
        if np.mean(gray) < 128:
            gray = cv2.bitwise_not(gray)

        # CLAHE
        clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
        enhanced = clahe.apply(gray)

        # Otsu threshold
        _, binary = cv2.threshold(enhanced, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

        return binary

    def extract(self, image: np.ndarray, odo_type: str = 'digital') -> dict:
        """
        Extract mileage reading from odometer image.
        Returns: {mileage, unit, confidence, raw_text, all_readings}
        """
        self._load()

        # Preprocess
        processed = self.preprocess(image, odo_type)

        # OCR with digit allowlist
        results = self.reader.readtext(processed, detail=1, paragraph=False,
                                       allowlist='0123456789. ')
        readings = [{'text': r[1].strip(), 'confidence': round(r[2], 4)}
                    for r in results if r[1].strip()]

        # Also try full OCR for context (km/miles detection)
        results_full = self.reader.readtext(processed, detail=1, paragraph=False)
        full_text = ' '.join([r[1].strip() for r in results_full if r[1].strip()])

        # Also try on original image (no preprocessing)
        h, w = image.shape[:2]
        resized = cv2.resize(image, (w * 2, h * 2), interpolation=cv2.INTER_CUBIC)
        results_orig = self.reader.readtext(resized, detail=1, paragraph=False)
        orig_text = ' '.join([r[1].strip() for r in results_orig if r[1].strip()])

        # Combine all texts for parsing
        digit_text = ' '.join([r['text'] for r in readings])
        all_text = f"{digit_text} {full_text} {orig_text}"

        # Parse mileage
        mileage_result = self._parse_mileage(all_text)
        mileage_result['all_readings'] = readings
        mileage_result['raw_text'] = full_text or digit_text

        return mileage_result

    def _parse_mileage(self, text: str) -> dict:
        """Parse mileage value from OCR text."""
        if not text:
            return {'mileage': None, 'unit': 'km', 'confidence': 0}

        text_lower = text.lower()

        # Detect unit
        unit = 'km'
        if re.search(r'\b(miles?|mi)\b', text_lower):
            unit = 'miles'

        # Character fix for common OCR errors
        cleaned = self._fix_chars(text)

        # Strategy 1: Number before "km"
        m = re.search(r'(\d[\d.,\s]{2,10}\d)\s*(?:km|kms|miles?)', cleaned.lower())
        if m:
            value = self._to_number(m.group(1))
            if value and 0 < value <= 999999:
                return {'mileage': value, 'unit': unit, 'confidence': 95}

        # Strategy 2: Explicit decimal
        m = re.search(r'(\d{3,6})\.(\d{1,2})', cleaned)
        if m:
            value = float(m.group(1) + '.' + m.group(2))
            if 0 < value <= 999999:
                return {'mileage': value, 'unit': unit, 'confidence': 90}

        # Strategy 3: Longest valid number
        numbers = re.findall(r'\d+\.?\d*', cleaned)
        candidates = [float(n) for n in numbers if 100 <= float(n) <= 999999]
        if candidates:
            # Prefer numbers in typical range
            ideal = [c for c in candidates if 1000 <= c <= 500000]
            value = max(ideal) if ideal else max(candidates)
            return {'mileage': value, 'unit': unit, 'confidence': 70}

        # Strategy 4: Any 3+ digit number
        all_nums = [float(n) for n in numbers if len(n) >= 3 and float(n) <= 999999]
        if all_nums:
            return {'mileage': max(all_nums), 'unit': unit, 'confidence': 40}

        return {'mileage': None, 'unit': unit, 'confidence': 0}

    def _fix_chars(self, text: str) -> str:
        """Fix common OCR character confusions."""
        char_map = {'O': '0', 'o': '0', 'I': '1', 'l': '1',
                    'S': '5', 's': '5', 'B': '8', 'Z': '2', 'G': '6'}
        result = list(text)
        for i, c in enumerate(result):
            if c in char_map:
                left = result[i-1] if i > 0 else ''
                right = result[i+1] if i < len(result)-1 else ''
                if (isinstance(left, str) and left.isdigit()) or \
                   (isinstance(right, str) and right.isdigit()):
                    result[i] = char_map[c]
        return ''.join(result)

    def _to_number(self, raw: str) -> float:
        """Convert raw string to number, removing commas/spaces."""
        cleaned = re.sub(r'[,\s]', '', raw)
        try:
            return float(cleaned)
        except ValueError:
            return None
