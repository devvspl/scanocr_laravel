"""
YOLOv8 Odometer Region Detector.
Locates odometer bounding box in full dashboard image.
"""
import os
import cv2
import numpy as np


class OdometerDetector:
    """YOLO-based odometer region detection."""

    def __init__(self, model_path: str, conf_threshold: float = 0.5):
        self.model = None
        self.model_path = model_path
        self.conf_threshold = conf_threshold
        self._loaded = False

    def _load(self):
        """Lazy load model on first use."""
        if self._loaded:
            return
        from ultralytics import YOLO
        if os.path.exists(self.model_path):
            self.model = YOLO(self.model_path)
            self._loaded = True
        else:
            raise FileNotFoundError(f"YOLO weights not found: {self.model_path}")

    def detect(self, image_path: str) -> list:
        """
        Detect odometer regions in image.
        Returns list of detections: [{bbox, confidence, class_id, class_name}]
        """
        self._load()
        results = self.model(image_path, verbose=False, conf=self.conf_threshold)

        detections = []
        for box in results[0].boxes:
            x1, y1, x2, y2 = box.xyxy[0].cpu().numpy().tolist()
            detections.append({
                'bbox': [int(x1), int(y1), int(x2), int(y2)],
                'confidence': round(box.conf[0].item(), 4),
                'class_id': int(box.cls[0].item()),
                'class_name': self.model.names[int(box.cls[0].item())],
            })

        # Sort by confidence descending
        detections.sort(key=lambda d: d['confidence'], reverse=True)
        return detections

    def detect_and_crop(self, image_path: str, padding: int = 10) -> tuple:
        """
        Detect and return cropped odometer region.
        Returns: (cropped_image, detection_info) or (full_image, None)
        """
        image = cv2.imread(image_path)
        if image is None:
            raise ValueError(f"Cannot read image: {image_path}")

        detections = self.detect(image_path)

        if detections:
            best = detections[0]
            x1, y1, x2, y2 = best['bbox']
            h, w = image.shape[:2]

            # Add padding
            x1 = max(0, x1 - padding)
            y1 = max(0, y1 - padding)
            x2 = min(w, x2 + padding)
            y2 = min(h, y2 + padding)

            cropped = image[y1:y2, x1:x2]
            best['bbox'] = [x1, y1, x2, y2]
            return cropped, best

        # No detection — return full image
        return image, None

    @property
    def is_available(self) -> bool:
        """Check if model weights exist."""
        return os.path.exists(self.model_path)
