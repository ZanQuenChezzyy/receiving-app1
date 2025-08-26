<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransmittalGudangTerimaDetail;
use Illuminate\Auth\Access\Response;

class TransmittalGudangTerimaDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any TransmittalGudangTerimaDetail');
    }

    public function view(User $user, TransmittalGudangTerimaDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View TransmittalGudangTerimaDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create TransmittalGudangTerimaDetail');
    }

    public function update(User $user, TransmittalGudangTerimaDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update TransmittalGudangTerimaDetail');
    }

    public function delete(User $user, TransmittalGudangTerimaDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete TransmittalGudangTerimaDetail');
    }

    public function restore(User $user, TransmittalGudangTerimaDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore TransmittalGudangTerimaDetail');
    }

    public function forceDelete(User $user, TransmittalGudangTerimaDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete TransmittalGudangTerimaDetail');
    }
}