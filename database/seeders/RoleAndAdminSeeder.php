<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super-admin role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'phone' => '13800000000',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );

        // Assign role to admin
        $admin->assignRole($role);
    }
}
