<?php
namespace App\Models\Generated;
use Illuminate\Database\Eloquent\Model;
class Scanner extends Model
{
    protected $table = 'gen_scanners';
    protected $fillable = ['title', 'document_no', 'document_date', 'document_type', 'remarks', 'upload_scan_copy', 'other'];
    protected $casts = [
        'upload_scan_copy' => 'array',
    ];
}
