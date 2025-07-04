<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name'];
    protected $appends = ['resource']; // Atribut sementara

    public function getResourceAttribute()
    {
        $words = explode(' ', $this->name);
        $count = count($words);

        if ($count >= 2) {
            return $words[$count - 2] . ' ' . $words[$count - 1]; // Ambil 2 kata terakhir
        }

        return end($words); // Jika cuma 1 kata, pakai kata terakhir saja
    }

    public function setResourceAttribute($value)
    {
        $this->attributes['resource'] = $value;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions', 'permission_id', 'role_id');
    }
}
