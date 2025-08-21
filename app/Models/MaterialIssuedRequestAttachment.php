<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialIssuedRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_issued_request_id',
        'file_path',
        'file_name',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialIssuedRequest::class, 'material_issued_request_id', 'id');
    }
}
