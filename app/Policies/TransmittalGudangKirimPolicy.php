<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalGudangKirim;
use Illuminate\Auth\Access\Response;

class TransmittalGudangKirimPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalGudangKirim');
    }

    public function view(User $user, TransmittalGudangKirim $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalGudangKirim');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalGudangKirim');
    }

    public function update(User $user, TransmittalGudangKirim $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalGudangKirim');
    }

    public function delete(User $user, TransmittalGudangKirim $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalGudangKirim');
    }

    public function restore(User $user, TransmittalGudangKirim $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalGudangKirim');
    }

    public function forceDelete(User $user, TransmittalGudangKirim $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalGudangKirim');
    }
}