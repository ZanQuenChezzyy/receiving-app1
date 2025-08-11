<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalVpKembaliDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_vp_kirim_id',
        'approval_vp_kembali_id',
        'code',
        'status',
        'total_item',
    ];

    public function approvalVpKirim(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ApprovalVpKirim::class, 'approval_vp_kirim_id', 'id');
    }

    public function approvalVpKembali(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ApprovalVpKembali::class, 'approval_vp_kembali_id', 'id');
    }
}
