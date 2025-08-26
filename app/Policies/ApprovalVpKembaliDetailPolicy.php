<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ApprovalVpKembaliDetail;
use Illuminate\Auth\Access\Response;

class ApprovalVpKembaliDetailPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any ApprovalVpKembaliDetail');
    }

    public function view(User $user, ApprovalVpKembaliDetail $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View ApprovalVpKembaliDetail');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create ApprovalVpKembaliDetail');
    }

    public function update(User $user, ApprovalVpKembaliDetail $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update ApprovalVpKembaliDetail');
    }

    public function delete(User $user, ApprovalVpKembaliDetail $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete ApprovalVpKembaliDetail');
    }

    public function restore(User $user, ApprovalVpKembaliDetail $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore ApprovalVpKembaliDetail');
    }

    public function forceDelete(User $user, ApprovalVpKembaliDetail $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete ApprovalVpKembaliDetail');
    }
}