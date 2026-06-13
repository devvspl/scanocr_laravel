"""
Full Odometer Extraction Pipeline.
Detection → Classification → OCR → Validation

Called by Laravel:
    python -m odometer.pipeline --file /path/to/image.jpg --save-crop 1

Output: JSON to stdout.
"""
import os
import sys
import json
import argparse
import traceback
import warnings
import cv2
import numpy as np

warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import (YOLO_WEIGHTS, VGG16_WEIGHTS, OCR_GPU, OCR_BBOX_PADDING,
                    YOLO_CONF_THRESHOLD, CLASS_NAMES)
from models.detector import OdometerDetector
from models.extractor import MileageExtractor


def validate_reading(value, unit):
    """Validate extracted reading."""
    if value is None:
        return {'is_valid': False, 'message': 'No reading detected'}
    if value < 0:
        return {'is_valid': False, 'message': 'Negative reading'}
    if value == 0:
        return {'is_valid': False, 'message': 'Zero reading'}
    if value > 999999:
        return {'is_valid': False, 'message': 'Exceeds maximum range'}
    if value < 100:
        return {'is_valid': False, 'message': 'Very low reading - verify'}
    return {'is_valid': True, 'message': 'Reading within normal range'}


def safe_load_image(path, max_dim=2000):
    """Load image with size limit."""
    img = cv2.imread(path)
    if img is None:
        raise ValueError(f"Cannot read: {path}")
    h, w = img.shape[:2]
    if max(h, w) > max_dim:
        scale = max_dim / max(h, w)
        img = cv2.resize(img, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
    return img


def run_pipeline(file_path: str, save_crop: bool = True) -> dict:
    """
    Run full extraction pipeline.
    1. YOLO detection (if model available)
    2. Classification (if model available)
    3. OCR extraction
    4. Validation
    """
    image = safe_load_image(file_path)
    h, w = image.shape[:2]

    detection_info = None
    odometer_type = 'unknown'
    cropped = image  # Default: full image
    method = 'full_image'

    # ── Stage 1: YOLO Detection ──────────────────────────────────────
    detector = OdometerDetector(YOLO_WEIGHTS, YOLO_CONF_THRESHOLD)

    if detector.is_available:
        try:
            cropped, detection_info = detector.detect_and_crop(file_path, padding=OCR_BBOX_PADDING)
            if detection_info:
                odometer_type = detection_info['class_name']
                method = 'yolo_detection'
        except Exception:
            pass  # Fall through to full image

    # ── Stage 2: Classification (if no YOLO or YOLO didn't classify) ─
    if odometer_type == 'unknown':
        try:
            from models.classifier import OdometerClassifier
            classifier = OdometerClassifier(VGG16_WEIGHTS)
            if classifier.is_available:
                cls_result = classifier.classify(cropped)
                odometer_type = cls_result['class_name']
                method = 'vgg16_classification' if method == 'full_image' else method
        except Exception:
            odometer_type = 'digital'  # Default assumption

    # ── Stage 3: OCR Extraction ──────────────────────────────────────
    extractor = MileageExtractor(gpu=OCR_GPU)
    ocr_result = extractor.extract(cropped, odo_type=odometer_type)

    mileage = ocr_result.get('mileage')
    unit = ocr_result.get('unit', 'km')
    confidence = ocr_result.get('confidence', 0)
    raw_text = ocr_result.get('raw_text', '')

    # ── Stage 4: Validation ──────────────────────────────────────────
    validation = validate_reading(mileage, unit)

    # ── Save crop ────────────────────────────────────────────────────
    cropped_path = None
    if save_crop and cropped is not None:
        crop_dir = os.path.join(os.path.dirname(file_path), 'crops')
        os.makedirs(crop_dir, exist_ok=True)
        crop_name = 'crop_' + os.path.basename(file_path)
        cropped_path = os.path.join(crop_dir, crop_name)
        cv2.imwrite(cropped_path, cropped)

    # ── Build response ───────────────────────────────────────────────
    bbox = None
    if detection_info:
        x1, y1, x2, y2 = detection_info['bbox']
        bbox = {'x': x1, 'y': y1, 'w': x2 - x1, 'h': y2 - y1}

    return {
        'success': True,
        'reading': mileage,
        'unit': unit,
        'confidence': confidence,
        'odometer_type': odometer_type,
        'method': method,
        'raw_ocr_text': raw_text,
        'ocr_confidence': confidence,
        'bounding_box': bbox,
        'cropped_image_path': cropped_path,
        'validation': validation,
        'detection': detection_info,
    }


def main():
    parser = argparse.ArgumentParser(description='Odometer Mileage Extraction')
    parser.add_argument('--file', required=True, help='Path to image')
    parser.add_argument('--save-crop', default='1', choices=['0', '1'])
    parser.add_argument('--type', default='auto', choices=['auto', 'digital', 'analog'],
                        help='Force odometer type (bypasses detection)')
    args = parser.parse_args()

    try:
        if not os.path.exists(args.file):
            print(json.dumps({'success': False, 'error': f'File not found: {args.file}'}))
            sys.exit(1)

        result = run_pipeline(args.file, save_crop=(args.save_crop == '1'))
        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e),
            'trace': traceback.format_exc()
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
