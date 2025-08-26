<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ApprovalVpKembali;
use Illuminate\Auth\Access\Response;

class ApprovalVpKembaliPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any ApprovalVpKembali');
    }

    public function view(User $user, ApprovalVpKembali $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View ApprovalVpKembali');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create ApprovalVpKembali');
    }

    public function update(User $user, ApprovalVpKembali $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update ApprovalVpKembali');
    }

    public function delete(User $user, ApprovalVpKembali $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete ApprovalVpKembali');
    }

    public function restore(User $user, ApprovalVpKembali $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore ApprovalVpKembali');
    }

    public function forceDelete(User $user, ApprovalVpKembali $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete ApprovalVpKembali');
    }
}