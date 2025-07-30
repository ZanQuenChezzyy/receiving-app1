<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalKembaliGrs extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_kembali',
        'created_by',
    ];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id');
    }


    public function transmittalKembaliGrsDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TransmittalKembaliGrsDetail::class);
    }

}
