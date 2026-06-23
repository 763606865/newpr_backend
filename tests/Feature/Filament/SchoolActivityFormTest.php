<?php

namespace Tests\Feature\Filament;

use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Filament\Resources\Rc\SchoolActivities\Pages\CreateSchoolActivity;
use App\Filament\Resources\Rc\SchoolActivities\Pages\EditSchoolActivity;
use App\Models\Area;
use App\Models\Rc\SchoolActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class SchoolActivityFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function schoolActivityPermissions(): array
    {
        return [
            'ViewAny:SchoolActivity',
            'View:SchoolActivity',
            'Create:SchoolActivity',
            'Update:SchoolActivity',
            'Delete:SchoolActivity',
            'DeleteAny:SchoolActivity',
            'Restore:SchoolActivity',
            'ForceDelete:SchoolActivity',
            'ForceDeleteAny:SchoolActivity',
            'RestoreAny:SchoolActivity',
        ];
    }

    public function test_create_school_activity_form_saves_rich_text_and_area_codes(): void
    {
        $this->actingAsFilamentAdmin($this->schoolActivityPermissions());

        $this->seedAreas();

        Livewire::test(CreateSchoolActivity::class)
            ->assertSuccessful()
            ->assertSee('活动类型')
            ->assertSee('封面图')
            ->assertSee('活动描述')
            ->assertSee('省份')
            ->assertSee('附件')
            ->assertDontSee('extra')
            ->fillForm([
                'type' => RcSchoolActivityType::Presentation,
                'title' => '测试宣讲会',
                'description' => '<p>活动详情</p>',
                'province_code' => '360000',
                'city_code' => '360100',
                'district_code' => '360103',
                'status' => RcSchoolActivityStatus::Draft,
                'is_hot' => false,
                'sort' => 0,
            ])
            ->call('create')
            ->assertNotified();

        $activity = SchoolActivity::query()->where('title', '测试宣讲会')->first();

        $this->assertNotNull($activity);
        $this->assertSame('<p>活动详情</p>', $activity->description);
        $this->assertSame('360000', $activity->province_code);
        $this->assertSame('360100', $activity->city_code);
        $this->assertSame('360103', $activity->district_code);
    }

    public function test_resolve_form_area_codes_fills_missing_province_from_city_code(): void
    {
        $this->seedAreas();

        $this->assertSame([
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360103',
        ], Area::resolveFormAreaCodes(null, '360100', '360103'));
    }

    public function test_edit_school_activity_form_loads_existing_record(): void
    {
        $this->actingAsFilamentAdmin($this->schoolActivityPermissions());

        $this->seedAreas();

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '春季双选会',
            'description' => '<p>双选会介绍</p>',
            'province_code' => '360000',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        Livewire::test(EditSchoolActivity::class, ['record' => $activity->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'title' => '春季双选会',
                'description' => '<p>双选会介绍</p>',
                'province_code' => '360000',
                'city_code' => '360100',
            ]);
    }

    public function test_edit_form_resolves_area_hierarchy_when_only_city_code_is_stored(): void
    {
        $this->actingAsFilamentAdmin($this->schoolActivityPermissions());

        $this->seedAreas();

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '仅保存城市编码的活动',
            'city_code' => '360100',
            'district_code' => '360103',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        Livewire::test(EditSchoolActivity::class, ['record' => $activity->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'province_code' => '360000',
                'city_code' => '360100',
                'district_code' => '360103',
            ]);
    }

    private function seedAreas(): void
    {
        Area::query()->create([
            'name' => '江西省',
            'code' => '360000',
            'parent_code' => '000000',
            'level' => 1,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '南昌市',
            'code' => '360100',
            'parent_code' => '360000',
            'level' => 2,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '西湖区',
            'code' => '360103',
            'parent_code' => '360100',
            'level' => 3,
            'type' => null,
        ]);
    }
}
