<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissions for users
        $permissions = [
            'View Any User',
            'View User',
            'Create User',
            'Update User',
            'Delete User',
            'Restore User',
            'Force Delete User',
            'View Any Role',
            'View Role',
            'Create Role',
            'Update Role',
            'Delete Role',
            'Restore Role',
            'Force Delete Role',
            'View Any Permission',
            'View Permission',
            'Create Permission',
            'Update Permission',
            'Delete Permission',
            'Restore Permission',
            'Force Delete Permission',
            'View Any Data',
            'View Data',
            'Create Data',
            'Update Data',
            'Delete Data',
            'Restore Data',
            'Force Delete Data',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $roles = [
            'Administrator' => [
                'View Any User',
                'View User',
                'Create User',
                'Update User',
                'Delete User',
                'Restore User',
                'Force Delete User',
                'View Any Role',
                'View Role',
                'Create Role',
                'Update Role',
                'Delete Role',
                'Restore Role',
                'Force Delete Role',
                'View Any Permission',
                'View Permission',
                'Create Permission',
                'Update Permission',
                'Delete Permission',
                'Restore Permission',
                'Force Delete Permission',
            ],
            'User' => [
                'View Any Data',
                'View Data',
                'Create Data',
                'Update Data',
                'Delete Data',
                'Restore Data',
                'Force Delete Data',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        // Create users and assign roles
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'Administrator',
            ],
            [
                'name' => 'User',
                'email' => 'user@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'User',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                ]
            );

            $user->assignRole($userData['role']);
        }

        $this->command->info('Roles, Permissions, dan Users Telah berhasil dibuat!');
    }
}
