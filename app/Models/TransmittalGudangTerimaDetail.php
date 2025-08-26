<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalGudangTerimaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_gudang_terima_id',
        'transmittal_gudang_kirim_detail_id',
        'qty_diterima',
        'catatan',
    ];

    public function transmittalGudangTerima(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalGudangTerima::class, 'transmittal_gudang_terima_id', 'id');
    }

    public function transmittalGudangKirimDetail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalGudangKirimDetail::class, 'transmittal_gudang_kirim_detail_id', 'id');
    }
}
