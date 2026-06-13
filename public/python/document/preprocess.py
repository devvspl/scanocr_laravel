"""
Image preprocessing before OCR.
Improves OCR accuracy via OpenCV.
"""
import cv2
import numpy as np
from PIL import Image


def preprocess_image(image_path: str) -> np.ndarray:
    """
    Load image and apply preprocessing pipeline:
    1. Read with OpenCV
    2. Convert to grayscale
    3. Resize if too small (min 1000px wide)
    4. Apply CLAHE (adaptive contrast)
    5. Gaussian blur for noise removal
    6. Otsu threshold for binarization

    Returns: preprocessed numpy array
    """
    img = cv2.imread(image_path)

    if img is None:
        # Try PIL fallback (handles some formats OpenCV misses)
        pil_img = Image.open(image_path).convert('RGB')
        img = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)

    # Grayscale
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # Resize if width < 1000px (improves OCR on small scans)
    h, w = gray.shape
    if w < 1000:
        scale = 1000 / w
        gray = cv2.resize(gray, None, fx=scale, fy=scale,
                          interpolation=cv2.INTER_CUBIC)

    # CLAHE - adaptive histogram equalization
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    # Gaussian blur - noise removal
    gray = cv2.GaussianBlur(gray, (3, 3), 0)

    # Otsu threshold - binarize
    _, binary = cv2.threshold(gray, 0, 255,
                              cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    return binary


def pdf_page_to_image(pdf_path: str, page_num: int) -> np.ndarray:
    """
    Convert one page of a PDF to numpy array using PyMuPDF.
    page_num: 0-indexed
    Returns: numpy array (RGB)
    """
    import fitz  # PyMuPDF

    doc = fitz.open(pdf_path)
    page = doc.load_page(page_num)

    # Render at 2x resolution for better OCR
    mat = fitz.Matrix(2.0, 2.0)
    pix = page.get_pixmap(matrix=mat)

    img = np.frombuffer(pix.samples, dtype=np.uint8)
    img = img.reshape(pix.height, pix.width, pix.n)

    if pix.n == 4:  # RGBA -> RGB
        img = cv2.cvtColor(img, cv2.COLOR_RGBA2RGB)

    doc.close()
    return img


def get_pdf_page_count(pdf_path: str) -> int:
    import fitz

    doc = fitz.open(pdf_path)
    count = doc.page_count
    doc.close()
    return count
