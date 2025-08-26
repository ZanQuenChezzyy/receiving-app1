<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalGudangKirimDetail;
use Illuminate\Auth\Access\Response;

class TransmittalGudangKirimDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalGudangKirimDetail');
    }

    public function view(User $user, TransmittalGudangKirimDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalGudangKirimDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalGudangKirimDetail');
    }

    public function update(User $user, TransmittalGudangKirimDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalGudangKirimDetail');
    }

    public function delete(User $user, TransmittalGudangKirimDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalGudangKirimDetail');
    }

    public function restore(User $user, TransmittalGudangKirimDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalGudangKirimDetail');
    }

    public function forceDelete(User $user, TransmittalGudangKirimDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalGudangKirimDetail');
    }
}