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

    protected static function booted()
    {
        static::deleted(function ($detail) {
            $detail->afterCommit(function () use ($detail) {
                $approvalVpKembali = $detail->approvalVpKembali()->first();

                if ($approvalVpKembali && $approvalVpKembali->approvalVpKembaliDetails()->count() === 0) {
                    $approvalVpKembali->delete();
                }
            });
        });
    }

    public function approvalVpKirim(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ApprovalVpKirim::class, 'approval_vp_kirim_id', 'id');
    }

    public function approvalVpKembali(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ApprovalVpKembali::class, 'approval_vp_kembali_id', 'id');
    }
}
