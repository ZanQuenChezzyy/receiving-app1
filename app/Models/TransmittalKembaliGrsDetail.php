<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKembaliGrsDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_kembali_id',
        'transmittal_kirim_id',
        'do_receipt_detail_id',
        'code',
        'code_105',
        'total_item',
    ];

    public function transmittalKirimGrs(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalKirimGrs::class, 'transmittal_kirim_grs_id', 'id');
    }


    public function transmittalKembaliGrs(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\TransmittalKembaliGrs::class, 'transmittal_kembali_grs_id', 'id');
    }

}