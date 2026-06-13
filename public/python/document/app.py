"""
Combined OCR + Predict in one call.
Avoids calling two separate Python processes from Laravel.

Called by Laravel:
    python app.py --file /path/to/file.pdf --training-json '[...]' --lang en

Output: single JSON combining OCR + prediction results.
{
    "success": true,
    "ocr_text": "full text...",
    "ocr_confidence": 87.5,
    "page_count": 2,
    "pages": {"1":"text","2":"text"},
    "prediction": {
        "basis_id": 8,
        "basis_name": "Freight Bill",
        "confidence": 94.25
    },
    "all_scores": [...]
}
"""
import sys
import json
import argparse
import warnings
import os
import traceback

warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# Import from sibling scripts
sys.path.insert(0, os.path.dirname(__file__))
from ocr import load_reader, process_image_file, process_pdf_file
from predictor import predict as ai_predict


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--file', required=True)
    parser.add_argument('--training-json', required=False, default=None)
    parser.add_argument('--training-json-file', required=False, default=None)
    parser.add_argument('--lang', default='en')
    args = parser.parse_args()

    try:
        # Step 1: OCR
        ext = os.path.splitext(args.file)[1].lower().lstrip('.')
        reader = load_reader(args.lang)

        if ext == 'pdf':
            ocr_result = process_pdf_file(reader, args.file)
        else:
            ocr_result = process_image_file(reader, args.file)

        if not ocr_result.get('success'):
            print(json.dumps(ocr_result))
            sys.exit(1)

        ocr_text = ocr_result['text']

        # Step 2: AI Prediction
        # Load training data from file or argument
        if args.training_json_file:
            with open(args.training_json_file, 'r', encoding='utf-8') as f:
                training_data = json.load(f)
        elif args.training_json:
            training_data = json.loads(args.training_json)
        else:
            print(json.dumps({'success': False, 'error': 'No training data provided'}))
            sys.exit(1)

        prediction_result = ai_predict(ocr_text, training_data)

        # Step 3: Combine and output
        response = {
            'success': True,
            'ocr_text': ocr_text,
            'ocr_confidence': ocr_result.get('ocr_confidence', 0),
            'page_count': ocr_result.get('page_count', 1),
            'pages': ocr_result.get('pages', {}),
            'prediction': prediction_result.get('prediction', {}),
            'all_scores': prediction_result.get('all_scores', []),
        }

        print(json.dumps(response))

    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e),
            'trace': traceback.format_exc()
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
