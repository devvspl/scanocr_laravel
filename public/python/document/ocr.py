"""
OCR extraction using EasyOCR.
Handles: JPG, PNG, JPEG (direct), PDF (page by page).

Called by Laravel:
    python ocr.py --file /path/to/file.pdf --lang en

Output: JSON only to stdout.
{
    "success": true,
    "text": "full extracted text",
    "pages": {"1": "page 1 text", "2": "page 2 text"},
    "page_count": 2,
    "ocr_confidence": 87.5
}
"""
import sys
import os
import json
import argparse
import traceback
import io
import warnings

warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# Redirect stderr to devnull during model load
_stderr = sys.stderr
sys.stderr = io.StringIO()

import easyocr
import numpy as np
from preprocess import preprocess_image, pdf_page_to_image, get_pdf_page_count


def load_reader(lang='en'):
    """Load EasyOCR reader. gpu=False for server compatibility."""
    reader = easyocr.Reader(
        [lang],
        gpu=False,
        verbose=False,
        model_storage_directory=os.path.join(
            os.path.dirname(__file__), 'easyocr_models'
        )
    )
    sys.stderr = _stderr  # restore stderr
    return reader


def extract_text_from_image_array(reader, img_array):
    """
    Run EasyOCR on a numpy image array.
    Returns: (text, avg_confidence)
    """
    results = reader.readtext(img_array, detail=1, paragraph=False)

    if not results:
        return '', 0.0

    texts = []
    confidences = []

    for (bbox, text, confidence) in results:
        if text.strip():
            texts.append(text.strip())
            confidences.append(confidence)

    full_text = ' '.join(texts)
    avg_conf = round(
        sum(confidences) / len(confidences) * 100, 2
    ) if confidences else 0.0

    return full_text, avg_conf


def process_image_file(reader, file_path: str) -> dict:
    """Process single image file (JPG/PNG/JPEG)."""
    preprocessed = preprocess_image(file_path)
    text, confidence = extract_text_from_image_array(reader, preprocessed)

    return {
        'success': True,
        'text': text,
        'pages': {'1': text},
        'page_count': 1,
        'ocr_confidence': confidence,
    }


def process_pdf_file(reader, file_path: str) -> dict:
    """Process multi-page PDF. Extract text from each page."""
    import cv2

    page_count = get_pdf_page_count(file_path)
    pages = {}
    all_text_parts = []
    all_confidences = []

    for page_num in range(page_count):
        img_array = pdf_page_to_image(file_path, page_num)

        # Preprocess: grayscale + threshold
        gray = cv2.cvtColor(img_array, cv2.COLOR_RGB2GRAY)
        _, binary = cv2.threshold(gray, 0, 255,
                                  cv2.THRESH_BINARY + cv2.THRESH_OTSU)

        text, conf = extract_text_from_image_array(reader, binary)
        pages[str(page_num + 1)] = text

        if text.strip():
            all_text_parts.append(text)
            all_confidences.append(conf)

    full_text = '\n\n--- Page Break ---\n\n'.join(all_text_parts)
    avg_conf = (
        round(sum(all_confidences) / len(all_confidences), 2)
        if all_confidences else 0.0
    )

    return {
        'success': True,
        'text': full_text,
        'pages': pages,
        'page_count': page_count,
        'ocr_confidence': avg_conf,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--file', required=True, help='Absolute path to file')
    parser.add_argument('--lang', default='en', help='OCR language code')
    args = parser.parse_args()

    file_path = args.file

    if not os.path.exists(file_path):
        print(json.dumps({
            'success': False,
            'error': f'File not found: {file_path}'
        }))
        sys.exit(1)

    ext = os.path.splitext(file_path)[1].lower().lstrip('.')
    allowed = ['pdf', 'jpg', 'jpeg', 'png']

    if ext not in allowed:
        print(json.dumps({
            'success': False,
            'error': f'Unsupported file type: {ext}'
        }))
        sys.exit(1)

    try:
        reader = load_reader(args.lang)

        if ext == 'pdf':
            result = process_pdf_file(reader, file_path)
        else:
            result = process_image_file(reader, file_path)

        print(json.dumps(result))

    except Exception as e:
        sys.stderr = _stderr
        print(json.dumps({
            'success': False,
            'error': str(e),
            'trace': traceback.format_exc()
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
