<?php

namespace Tests\Feature\Filament;

use App\Enums\CmsMenuAudienceType;
use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Menus\Pages\CreateMenu;
use App\Filament\Resources\Cms\Menus\Pages\EditMenu;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CmsMenuIdentityFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function menuPermissions(): array
    {
        return [
            'ViewAny:Menu',
            'View:Menu',
            'Create:Menu',
            'Update:Menu',
            'Delete:Menu',
            'DeleteAny:Menu',
            'Restore:Menu',
            'ForceDelete:Menu',
            'ForceDeleteAny:Menu',
            'RestoreAny:Menu',
        ];
    }

    public function test_create_menu_syncs_visible_identities(): void
    {
        $this->actingAsFilamentAdmin($this->menuPermissions());

        Livewire::test(CreateMenu::class)
            ->fillForm([
                'name' => '中测校园',
                'code' => 'home.school',
                'link_type' => 1,
                'target' => 1,
                'status' => CmsStatus::Enabled,
                'is_show' => true,
                'sort' => 0,
                'identity_types' => [
                    CmsMenuAudienceType::JobSeeker->value,
                    CmsMenuAudienceType::Recruiter->value,
                ],
            ])
            ->call('create')
            ->assertNotified();

        $menu = Menu::query()->where('code', 'home.school')->first();

        $this->assertNotNull($menu);
        $this->assertSame(
            [
                CmsMenuAudienceType::JobSeeker->value,
                CmsMenuAudienceType::Recruiter->value,
            ],
            $menu->menuIdentities()
                ->orderBy('identity_type')
                ->pluck('identity_type')
                ->map(static fn (mixed $identityType): int => $identityType instanceof CmsMenuAudienceType ? $identityType->value : (int) $identityType)
                ->all(),
        );
    }

    public function test_edit_menu_loads_and_updates_visible_identities(): void
    {
        $this->actingAsFilamentAdmin($this->menuPermissions());

        $menu = Menu::query()->create([
            'name' => '首页',
            'code' => 'home.index',
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::Guest,
        ]);

        Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'identity_types' => [CmsMenuAudienceType::Guest->value],
            ])
            ->fillForm([
                'identity_types' => [CmsMenuAudienceType::JobSeeker->value],
            ])
            ->call('save')
            ->assertNotified();

        $this->assertSame(
            [CmsMenuAudienceType::JobSeeker->value],
            $menu->fresh()
                ->menuIdentities()
                ->pluck('identity_type')
                ->map(static fn (mixed $identityType): int => $identityType instanceof CmsMenuAudienceType ? $identityType->value : (int) $identityType)
                ->all(),
        );
    }

    public function test_clearing_identities_makes_menu_visible_to_all_audiences(): void
    {
        $this->actingAsFilamentAdmin($this->menuPermissions());

        $menu = Menu::query()->create([
            'name' => '校园页',
            'code' => 'home.schools',
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::Recruiter,
        ]);

        Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
            ->fillForm([
                'identity_types' => [],
            ])
            ->call('save')
            ->assertNotified();

        $menu->refresh()->load('menuIdentities');

        $this->assertCount(0, $menu->menuIdentities);
        $this->assertTrue($menu->isVisibleToAudience(CmsMenuAudienceType::Guest));
    }
}
