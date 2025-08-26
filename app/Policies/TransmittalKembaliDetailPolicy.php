<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalKembaliDetail;
use Illuminate\Auth\Access\Response;

class TransmittalKembaliDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalKembaliDetail');
    }

    public function view(User $user, TransmittalKembaliDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalKembaliDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalKembaliDetail');
    }

    public function update(User $user, TransmittalKembaliDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalKembaliDetail');
    }

    public function delete(User $user, TransmittalKembaliDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalKembaliDetail');
    }

    public function restore(User $user, TransmittalKembaliDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalKembaliDetail');
    }

    public function forceDelete(User $user, TransmittalKembaliDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalKembaliDetail');
    }
}