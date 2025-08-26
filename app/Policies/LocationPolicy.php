<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Location;
use Illuminate\Auth\Access\Response;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        // ✅ "View Any {Model}"
        return $user->can('View Any Location');
    }

    public function view(User $user, Location $model): bool
    {
        // ✅ "View {Model}"
        return $user->can('View Location');
    }

    public function create(User $user): bool
    {
        // ✅ "Create {Model}"
        return $user->can('Create Location');
    }

    public function update(User $user, Location $model): bool
    {
        // ✅ "Update {Model}"
        return $user->can('Update Location');
    }

    public function delete(User $user, Location $model): bool
    {
        // ✅ "Delete {Model}"
        return $user->can('Delete Location');
    }

    public function restore(User $user, Location $model): bool
    {
        // ✅ "Restore {Model}"
        return $user->can('Restore Location');
    }

    public function forceDelete(User $user, Location $model): bool
    {
        // ✅ "Force Delete {Model}"
        return $user->can('Force Delete Location');
    }
}