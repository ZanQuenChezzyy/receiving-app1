<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalKirim;
use Illuminate\Auth\Access\Response;

class TransmittalKirimPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalKirim');
    }

    public function view(User $user, TransmittalKirim $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalKirim');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalKirim');
    }

    public function update(User $user, TransmittalKirim $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalKirim');
    }

    public function delete(User $user, TransmittalKirim $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalKirim');
    }

    public function restore(User $user, TransmittalKirim $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalKirim');
    }

    public function forceDelete(User $user, TransmittalKirim $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalKirim');
    }
}