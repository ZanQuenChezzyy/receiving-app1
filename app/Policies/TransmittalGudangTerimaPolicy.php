<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalGudangTerima;
use Illuminate\Auth\Access\Response;

class TransmittalGudangTerimaPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalGudangTerima');
    }

    public function view(User $user, TransmittalGudangTerima $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalGudangTerima');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalGudangTerima');
    }

    public function update(User $user, TransmittalGudangTerima $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalGudangTerima');
    }

    public function delete(User $user, TransmittalGudangTerima $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalGudangTerima');
    }

    public function restore(User $user, TransmittalGudangTerima $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalGudangTerima');
    }

    public function forceDelete(User $user, TransmittalGudangTerima $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalGudangTerima');
    }
}