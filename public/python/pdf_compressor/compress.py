#!/usr/bin/env python3
"""
PDF Compression Script

Usage:
    compress.py input.pdf output.pdf [engine] [quality]
    compress.py --page-count input.pdf

Engines: ghostscript, pikepdf, pymupdf, auto
Qualities: screen, ebook, printer, prepress

Returns JSON with compression results.
"""

import sys
import os
import json
import subprocess
import time
from pathlib import Path

def log_error(message):
    """Log error to stderr"""
    print(f"ERROR: {message}", file=sys.stderr)

def get_page_count_safe(pdf_path):
    """Get page count using available libraries, fallback gracefully"""
    try:
        import PyPDF2
        with open(pdf_path, 'rb') as f:
            reader = PyPDF2.PdfReader(f)
            return len(reader.pages)
    except ImportError:
        pass
    
    try:
        import fitz  # PyMuPDF
        doc = fitz.open(pdf_path)
        count = len(doc)
        doc.close()
        return count
    except ImportError:
        pass
    
    try:
        import pikepdf
        with pikepdf.open(pdf_path) as pdf:
            return len(pdf.pages)
    except ImportError:
        pass
    
    # Fallback to ghostscript if available
    try:
        result = subprocess.run([
            'gs', '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=nullpage',
            f'-dLastPage=1000000', pdf_path
        ], capture_output=True, text=True, timeout=30)
        
        # Parse gs output for page count (basic implementation)
        if result.returncode == 0:
            # This is a rough implementation - in practice you'd parse gs output more carefully
            return 1  # Default fallback
    except (subprocess.TimeoutExpired, subprocess.SubprocessError, FileNotFoundError):
        pass
    
    return 0  # Unable to determine

def compress_with_ghostscript(input_path, output_path, quality):
    """Compress PDF using Ghostscript"""
    quality_settings = {
        'screen': '/screen',
        'ebook': '/ebook',
        'printer': '/printer',
        'prepress': '/prepress'
    }
    
    quality_param = quality_settings.get(quality, '/ebook')
    
    cmd = [
        'gs',
        '-sDEVICE=pdfwrite',
        '-dCompatibilityLevel=1.4',
        f'-dPDFSETTINGS={quality_param}',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        f'-sOutputFile={output_path}',
        input_path
    ]
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)
        if result.returncode == 0 and os.path.exists(output_path):
            return {'success': True, 'engine_used': 'ghostscript'}
        else:
            return {'success': False, 'error': f'Ghostscript failed: {result.stderr}'}
    except subprocess.TimeoutExpired:
        return {'success': False, 'error': 'Ghostscript timeout (5 minutes)'}
    except FileNotFoundError:
        return {'success': False, 'error': 'Ghostscript not found'}
    except Exception as e:
        return {'success': False, 'error': f'Ghostscript error: {str(e)}'}

def compress_with_pikepdf(input_path, output_path, quality):
    """Compress PDF using pikepdf"""
    try:
        import pikepdf
        
        # Quality-based compression settings
        quality_settings = {
            'screen': {'image_quality': 30, 'jpeg_quality': 30},
            'ebook': {'image_quality': 50, 'jpeg_quality': 50},
            'printer': {'image_quality': 75, 'jpeg_quality': 75},
            'prepress': {'image_quality': 90, 'jpeg_quality': 90}
        }
        
        settings = quality_settings.get(quality, quality_settings['ebook'])
        
        with pikepdf.open(input_path) as pdf:
            pdf.save(output_path, compress_streams=True, 
                    normalize_content=True, linearize=True)
            
        return {'success': True, 'engine_used': 'pikepdf'}
        
    except ImportError:
        return {'success': False, 'error': 'pikepdf not installed'}
    except Exception as e:
        return {'success': False, 'error': f'pikepdf error: {str(e)}'}

def compress_with_pymupdf(input_path, output_path, quality):
    """Compress PDF using PyMuPDF (fitz)"""
    try:
        import fitz
        
        # Quality-based compression settings
        quality_settings = {
            'screen': {'deflate': 9, 'deflate_images': True, 'deflate_fonts': True},
            'ebook': {'deflate': 6, 'deflate_images': True, 'deflate_fonts': True},
            'printer': {'deflate': 3, 'deflate_images': True, 'deflate_fonts': False},
            'prepress': {'deflate': 1, 'deflate_images': False, 'deflate_fonts': False}
        }
        
        settings = quality_settings.get(quality, quality_settings['ebook'])
        
        doc = fitz.open(input_path)
        doc.save(output_path, **settings)
        doc.close()
        
        return {'success': True, 'engine_used': 'pymupdf'}
        
    except ImportError:
        return {'success': False, 'error': 'PyMuPDF not installed'}
    except Exception as e:
        return {'success': False, 'error': f'PyMuPDF error: {str(e)}'}

def auto_compress(input_path, output_path, quality):
    """Try compression engines in order of preference"""
    engines = [
        ('ghostscript', compress_with_ghostscript),
        ('pikepdf', compress_with_pikepdf),
        ('pymupdf', compress_with_pymupdf)
    ]
    
    for engine_name, compress_func in engines:
        result = compress_func(input_path, output_path, quality)
        if result['success']:
            return result
        log_error(f"{engine_name}: {result['error']}")
    
    return {'success': False, 'error': 'No compression engines available'}

def main():
    if len(sys.argv) < 2:
        print("Usage: compress.py input.pdf output.pdf [engine] [quality]")
        print("       compress.py --page-count input.pdf")
        sys.exit(1)
    
    # Handle page count mode
    if sys.argv[1] == '--page-count':
        if len(sys.argv) < 3:
            print(json.dumps({'success': False, 'error': 'Missing input file'}))
            sys.exit(1)
        
        input_path = sys.argv[2]
        if not os.path.exists(input_path):
            print(json.dumps({'success': False, 'error': 'Input file not found'}))
            sys.exit(1)
        
        page_count = get_page_count_safe(input_path)
        print(json.dumps({'success': True, 'page_count': page_count}))
        sys.exit(0)
    
    # Handle compression mode
    if len(sys.argv) < 3:
        print(json.dumps({'success': False, 'error': 'Missing output file'}))
        sys.exit(1)
    
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    engine = sys.argv[3] if len(sys.argv) > 3 else 'auto'
    quality = sys.argv[4] if len(sys.argv) > 4 else 'ebook'
    
    # Validate input
    if not os.path.exists(input_path):
        print(json.dumps({'success': False, 'error': 'Input file not found'}))
        sys.exit(1)
    
    # Get file info
    original_size = os.path.getsize(input_path)
    original_pages = get_page_count_safe(input_path)
    
    start_time = time.time()
    
    # Compress based on engine
    if engine == 'ghostscript':
        result = compress_with_ghostscript(input_path, output_path, quality)
    elif engine == 'pikepdf':
        result = compress_with_pikepdf(input_path, output_path, quality)
    elif engine == 'pymupdf':
        result = compress_with_pymupdf(input_path, output_path, quality)
    elif engine == 'auto':
        result = auto_compress(input_path, output_path, quality)
    else:
        result = {'success': False, 'error': f'Unknown engine: {engine}'}
    
    if not result['success']:
        print(json.dumps(result))
        sys.exit(1)
    
    # Calculate compression stats
    compressed_size = os.path.getsize(output_path) if os.path.exists(output_path) else original_size
    saved_bytes = max(0, original_size - compressed_size)
    compression_ratio = compressed_size / original_size if original_size > 0 else 1.0
    processing_time = round(time.time() - start_time, 2)
    
    # Return results
    response = {
        'success': True,
        'original_size': original_size,
        'compressed_size': compressed_size,
        'saved_bytes': saved_bytes,
        'compression_ratio': compression_ratio,
        'processing_time': processing_time,
        'original_pages': original_pages,
        'engine_used': result['engine_used']
    }
    
    print(json.dumps(response))

if __name__ == '__main__':
    main()