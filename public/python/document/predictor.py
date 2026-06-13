"""
AI Document Type Predictor using sentence-transformers.
Compares OCR text against training data from MySQL.

Called by Laravel:
    python predictor.py --text "invoice text..." --training-json '[{"id":1,"name":"Invoice","texts":["..."],"keywords":"invoice,bill"}]'

Output: JSON only to stdout.
{
    "success": true,
    "prediction": {
        "basis_id": 8,
        "basis_name": "Freight Bill",
        "confidence": 94.25
    },
    "all_scores": [
        {"basis_id": 1, "basis_name": "Invoice", "confidence": 45.2},
        ...
    ]
}

NOTE: Training data is passed as JSON argument from Laravel
(already queried from MySQL - Laravel passes it, Python doesn't
connect to MySQL directly).
"""
import sys
import json
import argparse
import warnings
import os
import traceback

warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'


def load_model():
    """Load sentence-transformers model (cached locally)."""
    from sentence_transformers import SentenceTransformer

    cache_dir = os.path.join(os.path.dirname(__file__), 'st_models')
    model = SentenceTransformer(
        'all-MiniLM-L6-v2',
        cache_folder=cache_dir
    )
    return model


def build_basis_text(basis: dict) -> str:
    """
    Combine all training texts and keywords for a basis
    into one representative text block.
    """
    parts = []

    if basis.get('texts'):
        parts.extend(basis['texts'])

    if basis.get('keywords'):
        kws = [k.strip() for k in basis['keywords'].split(',') if k.strip()]
        parts.append(' '.join(kws))

    # Add the basis_name itself multiple times (acts as anchor)
    parts.append(basis['name'])
    parts.append(basis['name'])

    return ' '.join(parts)


def keyword_bonus(ocr_text: str, keywords_str: str) -> float:
    """
    Calculate keyword match bonus score (0.0 to 0.25).
    Rewards predictions where keywords appear in OCR text.
    Extra bonus if the document type name itself appears in the text.
    """
    if not keywords_str:
        return 0.0

    ocr_lower = ocr_text.lower()
    keywords = [k.strip().lower() for k in keywords_str.split(',') if k.strip()]

    if not keywords:
        return 0.0

    matched = sum(1 for kw in keywords if kw in ocr_lower)
    ratio = matched / len(keywords)

    # Max bonus: 0.25 (25% boost if all keywords match)
    return round(ratio * 0.25, 4)


def predict(ocr_text: str, training_data: list) -> dict:
    """
    Main prediction function.

    training_data: list of dicts:
    [{"id":1,"name":"Invoice","texts":["..."],"keywords":"invoice,bill"}, ...]

    Returns prediction dict.
    """
    import numpy as np
    from sentence_transformers import util

    if not training_data:
        return {'success': False, 'error': 'No training data provided'}

    if not ocr_text.strip():
        return {'success': False, 'error': 'OCR text is empty'}

    model = load_model()

    # Encode OCR text
    ocr_embedding = model.encode(
        ocr_text[:2000],  # cap at 2000 chars
        convert_to_tensor=True
    )

    # Check first 500 chars for title (header area of document)
    ocr_header = ocr_text[:500].lower()
    ocr_full_lower = ocr_text.lower()

    all_scores = []

    for basis in training_data:
        basis_text = build_basis_text(basis)
        basis_embedding = model.encode(basis_text, convert_to_tensor=True)

        # Cosine similarity: -1 to 1 -> convert to 0-100
        similarity = util.cos_sim(ocr_embedding, basis_embedding).item()
        base_score = (similarity + 1) / 2 * 100  # normalize to 0-100

        # Add keyword bonus
        bonus = keyword_bonus(ocr_text, basis.get('keywords', ''))
        
        # Title detection bonus from training data title_patterns field
        title_bonus = 0.0
        basis_name = basis['name']
        patterns_str = basis.get('title_patterns', '')
        if patterns_str:
            patterns = [p.strip().lower() for p in patterns_str.split(',') if p.strip()]
            for pattern in patterns:
                if pattern in ocr_header:
                    title_bonus = 0.15  # 15% bonus for title in header
                    break
                elif pattern in ocr_full_lower:
                    title_bonus = 0.08  # 8% bonus for title anywhere
                    break

        # Negative penalty: if another type's title_patterns appear in header
        # (computed after all scores in a second pass)

        final_score = min(max(base_score + (bonus * 100) + (title_bonus * 100), 0), 100.0)

        all_scores.append({
            'basis_id': basis['id'],
            'basis_name': basis_name,
            'confidence': round(final_score, 2),
            '_title_bonus': title_bonus,
            '_patterns': patterns_str,
        })

    # Second pass: apply negative penalty
    # If any type got a title_bonus (meaning its name is in the header),
    # penalize other types that don't have a title match
    title_matched_ids = [s['basis_id'] for s in all_scores if s['_title_bonus'] > 0]
    if title_matched_ids:
        for s in all_scores:
            if s['basis_id'] not in title_matched_ids and s['_title_bonus'] == 0:
                # Penalize types that didn't match title
                s['confidence'] = round(max(s['confidence'] - 10, 0), 2)

    # Clean internal fields and sort
    for s in all_scores:
        del s['_title_bonus']
        del s['_patterns']

    all_scores.sort(key=lambda x: x['confidence'], reverse=True)

    best = all_scores[0]

    return {
        'success': True,
        'prediction': best,
        'all_scores': all_scores,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--text', required=True,
                        help='OCR extracted text')
    parser.add_argument('--training-json', required=False, default=None,
                        help='JSON string of training data from Laravel')
    parser.add_argument('--training-json-file', required=False, default=None,
                        help='Path to JSON file with training data')
    args = parser.parse_args()

    try:
        if args.training_json_file:
            with open(args.training_json_file, 'r', encoding='utf-8') as f:
                training_data = json.load(f)
        elif args.training_json:
            training_data = json.loads(args.training_json)
        else:
            print(json.dumps({'success': False, 'error': 'No training data provided'}))
            sys.exit(1)

        result = predict(args.text, training_data)
        print(json.dumps(result))

    except json.JSONDecodeError as e:
        print(json.dumps({
            'success': False,
            'error': f'Invalid training JSON: {str(e)}'
        }))
        sys.exit(1)

    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e),
            'trace': traceback.format_exc()
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
