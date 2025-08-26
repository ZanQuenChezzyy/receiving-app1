<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalKembali;
use Illuminate\Auth\Access\Response;

class TransmittalKembaliPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalKembali');
    }

    public function view(User $user, TransmittalKembali $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalKembali');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalKembali');
    }

    public function update(User $user, TransmittalKembali $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalKembali');
    }

    public function delete(User $user, TransmittalKembali $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalKembali');
    }

    public function restore(User $user, TransmittalKembali $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalKembali');
    }

    public function forceDelete(User $user, TransmittalKembali $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalKembali');
    }
}