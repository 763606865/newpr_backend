<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Models\AdminUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class AdminUserResourceTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function adminUserPermissions(): array
    {
        return [
            'ViewAny:AdminUser',
            'View:AdminUser',
            'Create:AdminUser',
            'Update:AdminUser',
            'Delete:AdminUser',
            'DeleteAny:AdminUser',
            'Replicate:AdminUser',
        ];
    }

    public function test_admin_can_create_an_admin_user_and_assign_roles(): void
    {
        $this->actingAsFilamentAdmin($this->adminUserPermissions());
        $role = Role::query()->create([
            'name' => 'editor',
            'guard_name' => 'admin',
        ]);

        Livewire::test(CreateAdminUser::class)
            ->fillForm([
                'name' => '内容管理员',
                'email' => 'content-admin@example.com',
                'password' => 'password123',
                'roles' => [$role->id],
            ])
            ->call('create')
            ->assertNotified();

        $admin = AdminUser::query()
            ->where('email', 'content-admin@example.com')
            ->sole();

        $this->assertSame('内容管理员', $admin->name);
        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertTrue($admin->hasRole($role));
    }

    public function test_admin_can_edit_an_admin_user_and_replace_roles_without_changing_password(): void
    {
        $this->actingAsFilamentAdmin($this->adminUserPermissions());
        $oldRole = Role::query()->create([
            'name' => 'editor',
            'guard_name' => 'admin',
        ]);
        $newRole = Role::query()->create([
            'name' => 'recruitment_operator',
            'guard_name' => 'admin',
        ]);
        $admin = AdminUser::query()->create([
            'name' => '旧名称',
            'email' => 'operator@example.com',
            'password' => 'original-password',
        ]);
        $admin->assignRole($oldRole);
        $originalPassword = $admin->password;

        Livewire::test(EditAdminUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm([
                'name' => '招聘运营',
                'email' => 'operator@example.com',
                'password' => '',
                'roles' => [$newRole->id],
            ])
            ->call('save')
            ->assertNotified();

        $admin->refresh();

        $this->assertSame('招聘运营', $admin->name);
        $this->assertSame($originalPassword, $admin->password);
        $this->assertTrue($admin->hasRole($newRole));
        $this->assertFalse($admin->hasRole($oldRole));
    }

    public function test_only_admin_users_with_roles_can_access_the_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');
        $adminWithoutRole = AdminUser::query()->create([
            'name' => '无角色管理员',
            'email' => 'no-role@example.com',
            'password' => 'password123',
        ]);
        $adminWithRole = AdminUser::query()->create([
            'name' => '有角色管理员',
            'email' => 'with-role@example.com',
            'password' => 'password123',
        ]);
        $role = Role::query()->create([
            'name' => 'custom_admin',
            'guard_name' => 'admin',
        ]);
        $adminWithRole->assignRole($role);

        $this->assertFalse($adminWithoutRole->canAccessPanel($panel));
        $this->assertTrue($adminWithRole->canAccessPanel($panel));
    }
}
