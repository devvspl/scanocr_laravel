<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubPunchFile extends Model
{
    protected $table      = 'sub_punchfile';
    protected $primaryKey = 'SubFileID';

    /**
     * Legacy table has no timestamp columns.
     */
    public $timestamps = false;

    protected $fillable = [
        'FileID',
        'Particular',
        'Qty',
        'Rate',
        'Amount',
        'Comment',
    ];

    protected $casts = [
        'Qty'    => 'decimal:3',
        'Rate'   => 'decimal:2',
        'Amount' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The parent punch entry this line item belongs to.
     * FK: sub_punchfile.FileID → punchfile.FileID
     */
    public function punchFile(): BelongsTo
    {
        return $this->belongsTo(PunchFile::class, 'FileID', 'FileID');
    }
}
