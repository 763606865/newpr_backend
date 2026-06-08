<?php

namespace Tests\Support;

use App\Models\AdminUser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait InteractsWithFilamentAdmin
{
    protected function actingAsFilamentAdmin(array $permissions = []): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'admin',
        ]);

        $permissions = $permissions !== []
            ? $permissions
            : [
                'ViewAny:Company',
                'View:Company',
                'Create:Company',
                'Update:Company',
                'Delete:Company',
                'DeleteAny:Company',
                'Restore:Company',
                'ForceDelete:Company',
                'ForceDeleteAny:Company',
                'RestoreAny:Company',
            ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $role->syncPermissions($permissions);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
