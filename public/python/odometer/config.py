"""
Configuration for Odometer Extraction System.
All paths, thresholds, and model settings in one place.
"""
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# ── Paths ─────────────────────────────────────────────────────────────
MODELS_DIR = os.path.join(BASE_DIR, 'weights')
DATASET_DIR = os.path.join(BASE_DIR, 'dataset')
TRODO_DIR = os.path.join(BASE_DIR, 'trodo-v01')
UPLOADS_DIR = os.path.join(BASE_DIR, 'static', 'uploads')
RESULTS_DIR = os.path.join(BASE_DIR, 'static', 'results')

# Model weight paths
YOLO_WEIGHTS = os.path.join(MODELS_DIR, 'yolo_odometer.pt')
VGG16_WEIGHTS = os.path.join(MODELS_DIR, 'vgg16_classifier.pth')

# ── YOLO Detection ────────────────────────────────────────────────────
YOLO_CONF_THRESHOLD = 0.5
YOLO_IMG_SIZE = 640
YOLO_EPOCHS = 50
YOLO_BATCH = 16

# ── Classification ────────────────────────────────────────────────────
CLASSIFIER_IMG_SIZE = 227
CLASSIFIER_EPOCHS = 50
CLASSIFIER_LR = 0.0001
CLASSIFIER_BATCH = 32

# ── OCR ───────────────────────────────────────────────────────────────
OCR_LANGUAGES = ['en']
OCR_GPU = False
OCR_ALLOWLIST = '0123456789.'
OCR_BBOX_PADDING = 10  # pixels padding around detected region

# ── Dataset ───────────────────────────────────────────────────────────
TRAIN_SPLIT = 0.80
VAL_SPLIT = 0.10
TEST_SPLIT = 0.10
RANDOM_SEED = 42

# ── Classes ───────────────────────────────────────────────────────────
CLASS_NAMES = ['analog', 'digital']
NUM_CLASSES = 2

# ── Ensure directories exist ──────────────────────────────────────────
for d in [MODELS_DIR, DATASET_DIR, UPLOADS_DIR, RESULTS_DIR]:
    os.makedirs(d, exist_ok=True)
