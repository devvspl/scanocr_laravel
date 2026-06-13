<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocumentTypeAccess extends Model
{
    protected $table = 'user_document_type_access';

    protected $fillable = ['user_id', 'document_type_id', 'can_view'];

    protected $casts = ['can_view' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
