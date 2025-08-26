<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalGudangKirimDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_gudang_kirim_id',
        'goods_receipt_slip_detail_id',
        'item_no',
        'quantity',
        'material_code',
        'description',
        'uoi',
    ];

    protected $appends = ['status_group'];

    public function getStatusGroupAttribute(): string
    {
        // nilai dari withCount() di query tabel
        $count = (int) ($this->terima_count ?? 0);

        return $count === 0 ? 'Outstanding' : 'Sudah Terima';
    }

    public function transmittalGudangKirim(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalGudangKirim::class, 'transmittal_gudang_kirim_id', 'id');
    }

    public function goodsReceiptSlipDetail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\GoodsReceiptSlipDetail::class, 'goods_receipt_slip_detail_id', 'id');
    }

    public function transmittalGudangTerimaDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalGudangTerimaDetail::class);
    }
}
