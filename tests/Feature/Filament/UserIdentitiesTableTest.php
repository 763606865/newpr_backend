<?php

namespace Tests\Feature\Filament;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Filament\Resources\Rc\UserIdentities\Pages\ListUserIdentities;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class UserIdentitiesTableTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_table_displays_user_name_and_removes_unneeded_columns(): void
    {
        $this->actingAsFilamentAdmin([
            'ViewAny:UserIdentity',
            'View:UserIdentity',
            'Update:UserIdentity',
        ]);

        $user = User::factory()->create(['name' => '招聘用户张三']);
        $identity = new UserIdentity([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'organization_name' => '示例科技',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);
        $identity->saveQuietly();

        Livewire::test(ListUserIdentities::class)
            ->assertSuccessful()
            ->assertTableColumnExists('user.name')
            ->assertTableColumnStateSet('user.name', '招聘用户张三', $identity)
            ->assertTableColumnDoesNotExist('user_id')
            ->assertTableColumnDoesNotExist('organization_type')
            ->assertTableColumnDoesNotExist('organization_id')
            ->assertTableColumnDoesNotExist('identity_name')
            ->assertTableColumnDoesNotExist('is_default')
            ->assertTableColumnDoesNotExist('updated_at');
    }
}
