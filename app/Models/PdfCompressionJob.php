<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PdfCompressionJob extends Model
{
    protected $fillable = [
        'original_filename',
        'original_path',
        'compressed_path', 
        'original_size',
        'compressed_size',
        'compression_ratio',
        'processing_time',
        'engine_used',
        'quality_setting',
        'status',
        'error_message',
        'created_by'
    ];

    protected $casts = [
        'original_size' => 'integer',
        'compressed_size' => 'integer',
        'compression_ratio' => 'decimal:4',
        'processing_time' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this compression job
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get jobs for the current user, most recent first
     */
    public static function forCurrentUser(int $limit = 20)
    {
        return static::where('created_by', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate saved bytes
     */
    public function getSavedBytesAttribute(): int
    {
        return max(0, $this->original_size - $this->compressed_size);
    }

    /**
     * Check if the job is completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->status === 'done';
    }

    /**
     * Check if the job failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->original_size);
    }

    /**
     * Get formatted compressed size
     */
    public function getFormattedCompressedSizeAttribute(): string
    {
        return $this->formatBytes($this->compressed_size);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return number_format($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
    }

    /**
     * Clean up old compression jobs and their files
     */
    public static function cleanupOldJobs(int $daysOld = 7): int
    {
        $cutoff = Carbon::now()->subDays($daysOld);
        
        $jobs = static::where('created_at', '<', $cutoff)->get();
        $count = 0;
        
        foreach ($jobs as $job) {
            // Delete associated files if they exist
            if ($job->original_path && file_exists($job->original_path)) {
                @unlink($job->original_path);
            }
            if ($job->compressed_path && file_exists($job->compressed_path)) {
                @unlink($job->compressed_path);
            }
            
            // Delete the database record
            $job->delete();
            $count++;
        }
        
        return $count;
    }
}