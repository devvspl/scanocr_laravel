"""
VGG16 Odometer Type Classifier.
Classifies cropped odometer region as analog or digital.
"""
import os
import cv2
import numpy as np
import torch
from torchvision import transforms


class OdometerClassifier:
    """VGG16-based analog/digital classifier."""

    def __init__(self, model_path: str, img_size: int = 227):
        self.model = None
        self.model_path = model_path
        self.img_size = img_size
        self._loaded = False
        self.class_names = ['analog', 'digital']
        self.transform = transforms.Compose([
            transforms.ToPILImage(),
            transforms.Resize((img_size, img_size)),
            transforms.ToTensor(),
            transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
        ])

    def _load(self):
        """Lazy load model."""
        if self._loaded:
            return
        from torchvision import models
        import torch.nn as nn

        model = models.vgg16(weights=None)
        model.classifier = nn.Sequential(
            nn.Linear(512 * 7 * 7, 4096),
            nn.ReLU(inplace=True),
            nn.Dropout(0.5),
            nn.Linear(4096, 1024),
            nn.ReLU(inplace=True),
            nn.Dropout(0.5),
            nn.Linear(1024, 1),
            nn.Sigmoid(),
        )

        if os.path.exists(self.model_path):
            model.load_state_dict(torch.load(self.model_path, map_location='cpu'))
            model.eval()
            self.model = model
            self._loaded = True
        else:
            raise FileNotFoundError(f"Classifier weights not found: {self.model_path}")

    def classify(self, image: np.ndarray) -> dict:
        """
        Classify odometer type.
        Returns: {class_name, confidence}
        """
        self._load()

        # Convert BGR to RGB
        rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        tensor = self.transform(rgb).unsqueeze(0)

        with torch.no_grad():
            output = self.model(tensor)
            prob = output.item()

        # prob > 0.5 = digital (class 1), else analog (class 0)
        if prob > 0.5:
            return {'class_name': 'digital', 'confidence': round(prob, 4)}
        else:
            return {'class_name': 'analog', 'confidence': round(1 - prob, 4)}

    @property
    def is_available(self) -> bool:
        """Check if model weights exist."""
        return os.path.exists(self.model_path)
