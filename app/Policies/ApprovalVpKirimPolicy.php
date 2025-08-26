<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ApprovalVpKirim;
use Illuminate\Auth\Access\Response;

class ApprovalVpKirimPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any ApprovalVpKirim');
    }

    public function view(User $user, ApprovalVpKirim $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View ApprovalVpKirim');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create ApprovalVpKirim');
    }

    public function update(User $user, ApprovalVpKirim $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update ApprovalVpKirim');
    }

    public function delete(User $user, ApprovalVpKirim $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete ApprovalVpKirim');
    }

    public function restore(User $user, ApprovalVpKirim $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore ApprovalVpKirim');
    }

    public function forceDelete(User $user, ApprovalVpKirim $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete ApprovalVpKirim');
    }
}