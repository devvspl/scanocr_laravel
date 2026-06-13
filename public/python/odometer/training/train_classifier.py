"""
Train VGG16 Transfer Learning classifier for analog vs digital odometer.

Usage:
    python -m odometer.training.train_classifier

Requires: dataset/ folder prepared by prepare_data.py
Output: weights/vgg16_classifier.pth
"""
import os
import sys
import torch
import torch.nn as nn
import torch.optim as optim
from torch.utils.data import DataLoader, Dataset
from torchvision import transforms, models
from PIL import Image

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from config import (DATASET_DIR, MODELS_DIR, CLASSIFIER_IMG_SIZE,
                    CLASSIFIER_EPOCHS, CLASSIFIER_LR, CLASSIFIER_BATCH,
                    CLASS_NAMES, NUM_CLASSES)


class OdometerDataset(Dataset):
    """Dataset for odometer classification (analog vs digital)."""

    def __init__(self, split: str, transform=None):
        self.transform = transform
        self.samples = []

        images_dir = os.path.join(DATASET_DIR, split, 'images')
        labels_dir = os.path.join(DATASET_DIR, split, 'labels')

        if not os.path.exists(images_dir):
            return

        for img_file in os.listdir(images_dir):
            if not img_file.lower().endswith(('.jpg', '.jpeg', '.png')):
                continue

            label_file = os.path.splitext(img_file)[0] + '.txt'
            label_path = os.path.join(labels_dir, label_file)

            if os.path.exists(label_path):
                with open(label_path, 'r') as f:
                    lines = f.readlines()
                if lines:
                    # Use first annotation's class
                    class_id = int(lines[0].strip().split()[0])
                    img_path = os.path.join(images_dir, img_file)
                    self.samples.append((img_path, class_id))

    def __len__(self):
        return len(self.samples)

    def __getitem__(self, idx):
        img_path, label = self.samples[idx]
        image = Image.open(img_path).convert('RGB')
        if self.transform:
            image = self.transform(image)
        return image, label


def build_vgg16_model():
    """Build VGG16 with transfer learning — freeze early layers."""
    model = models.vgg16(weights=models.VGG16_Weights.IMAGENET1K_V1)

    # Freeze all layers except last 5 conv layers
    for i, param in enumerate(model.features.parameters()):
        if i < 20:  # Freeze first 20 parameter groups
            param.requires_grad = False

    # Replace classifier for binary classification
    model.classifier = nn.Sequential(
        nn.Linear(512 * 7 * 7, 4096),
        nn.ReLU(inplace=True),
        nn.Dropout(0.5),
        nn.Linear(4096, 1024),
        nn.ReLU(inplace=True),
        nn.Dropout(0.5),
        nn.Linear(1024, 1),  # Binary output
        nn.Sigmoid(),
    )

    return model


def train_classifier():
    """Train VGG16 classifier."""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    print(f"Device: {device}")

    # Transforms
    transform = transforms.Compose([
        transforms.Resize((CLASSIFIER_IMG_SIZE, CLASSIFIER_IMG_SIZE)),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
    ])

    # Datasets
    train_dataset = OdometerDataset('train', transform=transform)
    val_dataset = OdometerDataset('val', transform=transform)

    if len(train_dataset) == 0:
        print("ERROR: No training data found. Run prepare_data.py first.")
        sys.exit(1)

    print(f"Train samples: {len(train_dataset)}")
    print(f"Val samples:   {len(val_dataset)}")

    train_loader = DataLoader(train_dataset, batch_size=CLASSIFIER_BATCH, shuffle=True, num_workers=0)
    val_loader = DataLoader(val_dataset, batch_size=CLASSIFIER_BATCH, shuffle=False, num_workers=0)

    # Model
    model = build_vgg16_model().to(device)
    criterion = nn.BCELoss()
    optimizer = optim.Adam(filter(lambda p: p.requires_grad, model.parameters()), lr=CLASSIFIER_LR)

    print(f"\nTraining VGG16 for {CLASSIFIER_EPOCHS} epochs...")
    print("=" * 60)

    best_val_acc = 0.0

    for epoch in range(CLASSIFIER_EPOCHS):
        # Train
        model.train()
        train_loss = 0.0
        train_correct = 0
        train_total = 0

        for images, labels in train_loader:
            images = images.to(device)
            labels = labels.float().unsqueeze(1).to(device)

            optimizer.zero_grad()
            outputs = model(images)
            loss = criterion(outputs, labels)
            loss.backward()
            optimizer.step()

            train_loss += loss.item()
            predicted = (outputs > 0.5).float()
            train_correct += (predicted == labels).sum().item()
            train_total += labels.size(0)

        train_acc = train_correct / train_total if train_total > 0 else 0

        # Validate
        model.eval()
        val_correct = 0
        val_total = 0

        with torch.no_grad():
            for images, labels in val_loader:
                images = images.to(device)
                labels = labels.float().unsqueeze(1).to(device)
                outputs = model(images)
                predicted = (outputs > 0.5).float()
                val_correct += (predicted == labels).sum().item()
                val_total += labels.size(0)

        val_acc = val_correct / val_total if val_total > 0 else 0

        if (epoch + 1) % 5 == 0 or epoch == 0:
            print(f"  Epoch {epoch+1:3d}/{CLASSIFIER_EPOCHS} | "
                  f"Loss: {train_loss/len(train_loader):.4f} | "
                  f"Train Acc: {train_acc:.4f} | Val Acc: {val_acc:.4f}")

        # Save best
        if val_acc > best_val_acc:
            best_val_acc = val_acc
            torch.save(model.state_dict(), os.path.join(MODELS_DIR, 'vgg16_classifier.pth'))

    print("=" * 60)
    print(f"✓ Best validation accuracy: {best_val_acc:.4f}")
    print(f"✓ Model saved to: {os.path.join(MODELS_DIR, 'vgg16_classifier.pth')}")


if __name__ == '__main__':
    train_classifier()
