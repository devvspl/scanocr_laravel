"""
Train YOLOv8 Object Detection model for odometer region detection.

Usage:
    python -m odometer.training.train_detector

Requires: dataset/ folder prepared by prepare_data.py
Output: weights/yolo_odometer.pt
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from config import (DATASET_DIR, MODELS_DIR, YOLO_EPOCHS, YOLO_BATCH, YOLO_IMG_SIZE)


def train_yolo():
    """Train YOLOv8 nano model on TRODO dataset."""
    from ultralytics import YOLO

    data_yaml = os.path.join(DATASET_DIR, 'data.yaml')
    if not os.path.exists(data_yaml):
        print("ERROR: data.yaml not found. Run prepare_data.py first.")
        sys.exit(1)

    print("=" * 60)
    print("  YOLOv8 Odometer Detection Training")
    print("=" * 60)
    print(f"  Dataset: {data_yaml}")
    print(f"  Epochs:  {YOLO_EPOCHS}")
    print(f"  Batch:   {YOLO_BATCH}")
    print(f"  ImgSize: {YOLO_IMG_SIZE}")
    print("=" * 60)

    # Load pretrained YOLOv8 nano
    model = YOLO("yolov8n.pt")

    # Train
    results = model.train(
        data=data_yaml,
        epochs=YOLO_EPOCHS,
        imgsz=YOLO_IMG_SIZE,
        batch=YOLO_BATCH,
        project=os.path.join(MODELS_DIR, 'runs'),
        name='odometer_detect',
        exist_ok=True,
        verbose=True,
    )

    # Copy best weights
    best_weights = os.path.join(MODELS_DIR, 'runs', 'odometer_detect', 'weights', 'best.pt')
    if os.path.exists(best_weights):
        import shutil
        dst = os.path.join(MODELS_DIR, 'yolo_odometer.pt')
        shutil.copy2(best_weights, dst)
        print(f"\n✓ Best weights saved to: {dst}")
    else:
        print("\n✗ Training completed but best.pt not found")

    return results


if __name__ == '__main__':
    train_yolo()
