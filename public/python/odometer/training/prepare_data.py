"""
Dataset Preparation: Convert TRODO Pascal VOC XML → YOLO format + split.

Usage:
    python -m odometer.training.prepare_data

Expects trodo-v01/ folder with:
    - images/
    - pascal voc 1.1/Annotations/
    - ground truth/groundtruth.json
"""
import os
import sys
import json
import shutil
import random
import xml.etree.ElementTree as ET

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from config import (TRODO_DIR, DATASET_DIR, TRAIN_SPLIT, VAL_SPLIT,
                    TEST_SPLIT, RANDOM_SEED, CLASS_NAMES)


def parse_voc_xml(xml_path: str, img_width: int, img_height: int) -> list:
    """Parse Pascal VOC XML annotation to YOLO format."""
    tree = ET.parse(xml_path)
    root = tree.getroot()
    labels = []

    for obj in root.findall('object'):
        name = obj.find('name').text.lower().strip()
        if name not in CLASS_NAMES:
            continue
        class_id = CLASS_NAMES.index(name)

        bbox = obj.find('bndbox')
        xmin = int(bbox.find('xmin').text)
        ymin = int(bbox.find('ymin').text)
        xmax = int(bbox.find('xmax').text)
        ymax = int(bbox.find('ymax').text)

        # Convert to YOLO format (normalized center x, y, width, height)
        x_center = ((xmin + xmax) / 2) / img_width
        y_center = ((ymin + ymax) / 2) / img_height
        width = (xmax - xmin) / img_width
        height = (ymax - ymin) / img_height

        labels.append(f"{class_id} {x_center:.6f} {y_center:.6f} {width:.6f} {height:.6f}")

    return labels


def get_image_dimensions(img_path: str) -> tuple:
    """Get image dimensions without loading full image."""
    import cv2
    img = cv2.imread(img_path)
    if img is None:
        return None, None
    return img.shape[1], img.shape[0]  # width, height


def prepare_dataset():
    """Convert TRODO dataset to YOLO format and split."""
    images_dir = os.path.join(TRODO_DIR, 'images')
    annotations_dir = os.path.join(TRODO_DIR, 'pascal voc 1.1', 'Annotations')

    if not os.path.exists(images_dir):
        print(f"ERROR: Dataset not found at {images_dir}")
        print(f"Download from: https://data.mendeley.com/datasets/6y8m379mkt/2")
        print(f"Extract to: {TRODO_DIR}/")
        sys.exit(1)

    # Get all image files
    image_files = [f for f in os.listdir(images_dir)
                   if f.lower().endswith(('.jpg', '.jpeg', '.png', '.bmp'))]
    print(f"Found {len(image_files)} images")

    # Create dataset splits
    random.seed(RANDOM_SEED)
    random.shuffle(image_files)

    n_train = int(len(image_files) * TRAIN_SPLIT)
    n_val = int(len(image_files) * VAL_SPLIT)

    splits = {
        'train': image_files[:n_train],
        'val': image_files[n_train:n_train + n_val],
        'test': image_files[n_train + n_val:],
    }

    # Create directory structure
    for split in ['train', 'val', 'test']:
        os.makedirs(os.path.join(DATASET_DIR, split, 'images'), exist_ok=True)
        os.makedirs(os.path.join(DATASET_DIR, split, 'labels'), exist_ok=True)

    # Process each split
    stats = {'train': 0, 'val': 0, 'test': 0, 'skipped': 0}

    for split, files in splits.items():
        for img_file in files:
            img_path = os.path.join(images_dir, img_file)
            xml_name = os.path.splitext(img_file)[0] + '.xml'
            xml_path = os.path.join(annotations_dir, xml_name)

            # Get image dimensions
            w, h = get_image_dimensions(img_path)
            if w is None:
                stats['skipped'] += 1
                continue

            # Parse annotation if exists
            labels = []
            if os.path.exists(xml_path):
                labels = parse_voc_xml(xml_path, w, h)

            if not labels:
                stats['skipped'] += 1
                continue

            # Copy image
            dst_img = os.path.join(DATASET_DIR, split, 'images', img_file)
            shutil.copy2(img_path, dst_img)

            # Write YOLO label
            label_file = os.path.splitext(img_file)[0] + '.txt'
            dst_label = os.path.join(DATASET_DIR, split, 'labels', label_file)
            with open(dst_label, 'w') as f:
                f.write('\n'.join(labels))

            stats[split] += 1

    print(f"\nDataset prepared:")
    print(f"  Train: {stats['train']} images")
    print(f"  Val:   {stats['val']} images")
    print(f"  Test:  {stats['test']} images")
    print(f"  Skipped: {stats['skipped']} (no annotation or corrupt)")

    # Create data.yaml for YOLO training
    data_yaml = os.path.join(DATASET_DIR, 'data.yaml')
    with open(data_yaml, 'w') as f:
        f.write(f"train: {os.path.join(DATASET_DIR, 'train', 'images')}\n")
        f.write(f"val: {os.path.join(DATASET_DIR, 'val', 'images')}\n")
        f.write(f"test: {os.path.join(DATASET_DIR, 'test', 'images')}\n")
        f.write(f"nc: {len(CLASS_NAMES)}\n")
        f.write(f"names: {CLASS_NAMES}\n")

    print(f"\ndata.yaml written to: {data_yaml}")


if __name__ == '__main__':
    prepare_dataset()
