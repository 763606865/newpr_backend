<?php

namespace Tests\Feature\Filament;

use App\Enums\AreaLevel;
use App\Enums\CmsAdType;
use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Ads\Pages\CreateAd;
use App\Filament\Resources\Cms\Ads\Pages\EditAd;
use App\Models\Area;
use App\Models\Cms\Ad;
use App\Models\Cms\AdSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class AdCityCodeFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function adPermissions(): array
    {
        return [
            'ViewAny:Ad',
            'View:Ad',
            'Create:Ad',
            'Update:Ad',
            'Delete:Ad',
            'DeleteAny:Ad',
            'Restore:Ad',
            'ForceDelete:Ad',
            'ForceDeleteAny:Ad',
            'RestoreAny:Ad',
        ];
    }

    public function test_create_ad_saves_city_code_from_cascade_fields(): void
    {
        $this->actingAsFilamentAdmin($this->adPermissions());
        $this->seedJiangxiAreas();
        $slot = $this->createAdSlot();

        Livewire::test(CreateAd::class)
            ->fillForm([
                'slot_id' => $slot->id,
                'title' => '南昌广告',
                'type' => CmsAdType::Image,
                'status' => CmsStatus::Enabled,
                'province_code' => '360000',
                'area_city_code' => '360100',
                'city_code' => '360100',
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('cms_ads', [
            'title' => '南昌广告',
            'city_code' => '360100',
        ]);
    }

    public function test_edit_ad_fills_city_cascade_fields_from_city_code(): void
    {
        $this->actingAsFilamentAdmin($this->adPermissions());
        $this->seedJiangxiAreas();
        $slot = $this->createAdSlot();
        $ad = Ad::query()->create([
            'slot_id' => $slot->id,
            'city_code' => '360100',
            'title' => '待编辑广告',
            'type' => CmsAdType::Image,
            'status' => CmsStatus::Enabled,
        ]);

        Livewire::test(EditAd::class, ['record' => $ad->getRouteKey()])
            ->assertFormSet([
                'province_code' => '360000',
                'area_city_code' => '360100',
                'city_code' => '360100',
            ]);
    }

    private function seedJiangxiAreas(): void
    {
        Area::query()->create([
            'code' => '360000',
            'name' => '江西省',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '360100',
            'name' => '南昌市',
            'parent_code' => '360000',
            'level' => AreaLevel::City,
        ]);
    }

    private function createAdSlot(): AdSlot
    {
        return AdSlot::query()->create([
            'name' => '首页横幅',
            'code' => 'home-banner',
            'type' => CmsAdType::Image,
            'status' => CmsStatus::Enabled,
        ]);
    }
}
