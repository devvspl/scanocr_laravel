<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PdfCompressionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PdfCompressorController extends Controller
{
    private string $pythonBin;
    private string $scriptDir;
    private string $storagePath;

    public function __construct()
    {
        $this->scriptDir   = public_path('python/pdf_compressor');
        $this->storagePath = storage_path('app/public/pdf-compressor');

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }

        // Prefer venv inside public/python/ (shared with DocumentAi)
        $venvPython = public_path('python/venv') . DIRECTORY_SEPARATOR
            . (PHP_OS_FAMILY === 'Windows' ? 'Scripts' : 'bin') . DIRECTORY_SEPARATOR . 'python';

        if (PHP_OS_FAMILY === 'Windows') {
            $venvPython .= '.exe';
        }

        if (file_exists($venvPython)) {
            $this->pythonBin = '"' . $venvPython . '"';
        } else {
            exec('python3 --version 2>&1', $out, $code);
            $this->pythonBin = ($code === 0) ? 'python3' : 'python';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAGE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /tools/pdf-compressor
     */
    public function index()
    {
        $jobs = PdfCompressionJob::forCurrentUser(20);

        return view('panel.tools.pdf-compressor.index', compact('jobs'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /tools/pdf-compressor/compress
     * Upload + compress. Returns JSON progress-compatible response.
     */
    public function compress(Request $request)
    {
        set_time_limit(1800); // 30 minutes for large files

        // Check for upload errors first
        if (!$request->hasFile('pdf_file')) {
            return response()->json([
                'success' => false,
                'message' => 'No file was uploaded. Please check your file size and PHP configuration.',
            ], 422);
        }

        $file = $request->file('pdf_file');
        if (!$file->isValid()) {
            $error = $file->getError();
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload_max_filesize limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload.',
            ];
            
            $message = $errorMessages[$error] ?? 'Unknown upload error.';
            return response()->json([
                'success' => false,
                'message' => "Upload failed: {$message} (Error code: {$error})",
            ], 422);
        }

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:512000', // 500 MB
            'engine'   => 'required|in:ghostscript,pikepdf,pymupdf,auto',
            'quality'  => 'required|in:screen,ebook,printer,prepress',
        ]);

        $originalName = $file->getClientOriginalName();
        $storedName   = time() . '_' . uniqid() . '_original.pdf';
        $file->move($this->storagePath, $storedName);
        $originalPath = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;
        $originalSize = filesize($originalPath);

        // Get page count via Python before compression
        $pages = $this->getPageCount($originalPath);

        // Insert job record
        $jobId = DB::table('pdf_compression_jobs')->insertGetId([
            'created_by'           => Auth::id(),
            'original_filename'    => $originalName,
            'original_stored_name' => $storedName,
            'original_size'        => $originalSize,
            'original_pages'       => $pages,
            'engine'               => $request->input('engine'),
            'quality'              => $request->input('quality'),
            'status'               => 'processing',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        try {
            $compressedName = time() . '_' . uniqid() . '_compressed.pdf';
            $compressedPath = $this->storagePath . DIRECTORY_SEPARATOR . $compressedName;

            $start  = microtime(true);
            $result = $this->runPython($originalPath, $compressedPath, $request->input('engine'), $request->input('quality'));
            $elapsed = round(microtime(true) - $start, 2);

            if (! $result['success'] || ! file_exists($compressedPath)) {
                throw new \RuntimeException($result['error'] ?? 'Python script produced no output file.');
            }

            $compressedSize = filesize($compressedPath);
            $ratio          = $originalSize > 0 ? round($compressedSize / $originalSize, 4) : 1.0;

            DB::table('pdf_compression_jobs')->where('id', $jobId)->update([
                'compressed_stored_name' => $compressedName,
                'compressed_size'        => $compressedSize,
                'compression_ratio'      => $ratio,
                'processing_time'        => $elapsed,
                'engine_used'            => $result['engine_used'] ?? $request->input('engine'),
                'status'                 => 'done',
                'updated_at'             => now(),
            ]);

            return response()->json([
                'success'         => true,
                'job_id'          => $jobId,
                'original_name'   => $originalName,
                'original_size'   => $originalSize,
                'original_pages'  => $pages,
                'compressed_size' => $compressedSize,
                'ratio'           => $ratio,
                'saved_bytes'     => max(0, $originalSize - $compressedSize),
                'processing_time' => $elapsed,
                'engine_used'     => $result['engine_used'] ?? $request->input('engine'),
                'download_url'    => route('tools.pdf-compressor.download', $jobId),
            ]);

        } catch (\Throwable $e) {
            Log::error('PDF compressor failed', ['job' => $jobId, 'error' => $e->getMessage()]);

            DB::table('pdf_compression_jobs')->where('id', $jobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Compression failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /tools/pdf-compressor/{job}/download
     */
    public function download($id)
    {
        $job = DB::table('pdf_compression_jobs')
            ->where('id', $id)
            ->where('created_by', Auth::id())
            ->where('status', 'done')
            ->firstOrFail();

        $path = $this->storagePath . DIRECTORY_SEPARATOR . $job->compressed_stored_name;

        if (! file_exists($path)) {
            abort(404, 'Compressed file not found.');
        }

        $downloadName = pathinfo($job->original_filename, PATHINFO_FILENAME) . '_compressed.pdf';

        return response()->download($path, $downloadName, ['Content-Type' => 'application/pdf']);
    }

    /**
     * DELETE /tools/pdf-compressor/{job}
     */
    public function destroy($id)
    {
        $job = DB::table('pdf_compression_jobs')
            ->where('id', $id)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        // Delete both files from storage
        foreach ([$job->original_stored_name, $job->compressed_stored_name] as $name) {
            if ($name) {
                $path = $this->storagePath . DIRECTORY_SEPARATOR . $name;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }

        DB::table('pdf_compression_jobs')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Job deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Run compress.py via proc_open — same pattern as DocumentAiController.
     */
    private function runPython(string $inputPath, string $outputPath, string $engine, string $quality): array
    {
        $script = $this->scriptDir . DIRECTORY_SEPARATOR . 'compress.py';

        if (! file_exists($script)) {
            return ['success' => false, 'error' => 'compress.py not found at: ' . $script];
        }

        $cmd = $this->pythonBin . ' '
            . '"' . $script . '" '
            . '"' . $inputPath . '" '
            . '"' . $outputPath . '" '
            . escapeshellarg($engine) . ' '
            . escapeshellarg($quality);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $this->scriptDir);
        $stdout  = '';
        $stderr  = '';

        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            return ['success' => false, 'error' => 'Failed to start Python process.'];
        }

        $output = json_decode(trim($stdout), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON from Python: ' . $stdout . ' | STDERR: ' . $stderr];
        }

        return $output;
    }

    /**
     * Quick page count via Python (non-critical — returns 0 on failure).
     */
    private function getPageCount(string $pdfPath): int
    {
        $script = $this->scriptDir . DIRECTORY_SEPARATOR . 'compress.py';
        if (! file_exists($script)) {
            return 0;
        }

        $cmd    = $this->pythonBin . ' "' . $script . '" --page-count "' . $pdfPath . '" 2>&1';
        $output = @shell_exec($cmd);

        $data = json_decode(trim($output ?? ''), true);

        return (int) ($data['pages'] ?? 0);
    }
}
