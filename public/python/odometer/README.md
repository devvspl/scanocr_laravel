# Odometer Mileage Extraction System

## Quick Start

### 1. Install Dependencies
```bash
cd public/python/odometer
pip install -r requirements.txt
```

### 2. Download TRODO Dataset
Download from: https://data.mendeley.com/datasets/6y8m379mkt/2
Extract to: `public/python/odometer/trodo-v01/`

### 3. Prepare Dataset
```bash
python -m training.prepare_data
```

### 4. Train YOLO Detector (requires GPU, ~1-2 hours)
```bash
python -m training.train_detector
```

### 5. Train VGG16 Classifier (requires GPU, ~30 min)
```bash
python -m training.train_classifier
```

### 6. Run Inference
```bash
python -m pipeline --file /path/to/odometer.jpg --save-crop 1
```

## Architecture
```
Input Image
    → YOLO Detection (locates odometer region)
    → VGG16 Classification (analog vs digital)
    → EasyOCR Extraction (reads digits)
    → Validation (range check)
    → JSON Output
```

## Without Trained Models
If YOLO/VGG16 weights are not available, the system falls back to:
- Full image OCR (no region detection)
- Default type assumption (digital)
- Still works, just less accurate

## File Structure
```
odometer/
├── config.py              # All settings
├── pipeline.py            # Main entry point (called by Laravel)
├── data.yaml              # YOLO training config
├── requirements.txt       # Python dependencies
├── models/
│   ├── detector.py        # YOLO wrapper
│   ├── classifier.py      # VGG16 wrapper
│   └── extractor.py       # EasyOCR wrapper
├── training/
│   ├── prepare_data.py    # Dataset conversion
│   ├── train_detector.py  # YOLO training
│   └── train_classifier.py # VGG16 training
├── weights/               # Trained model files (generated)
├── dataset/               # Processed dataset (generated)
└── trodo-v01/             # Raw dataset (download)
```
