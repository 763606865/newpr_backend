<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Filament\Resources\Rc\SchoolActivities\Pages\ListSchoolActivities;
use App\Models\Area;
use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class SchoolActivitiesListTableTest extends TestCase
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

    public function test_school_activities_list_displays_chinese_columns_region_and_organizer(): void
    {
        $this->actingAsFilamentAdmin($this->schoolActivityPermissions());

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

        $school = School::query()->create([
            'school_code' => '4136010403',
            'name' => '南昌大学',
        ]);

        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '高校宣讲活动',
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360103',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '企业宣讲活动',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::Company,
            'organizer_id' => $company->id,
            'is_hot' => true,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        Livewire::test(ListSchoolActivities::class)
            ->assertSuccessful()
            ->assertSee('标题')
            ->assertSee('所在地区')
            ->assertSee('开始时间')
            ->assertSee('结束时间')
            ->assertSee('主办方')
            ->assertSee('联系人')
            ->assertSee('联系电话')
            ->assertSee('状态')
            ->assertSee('热门')
            ->assertSee('江西省-南昌市-西湖区')
            ->assertSee('南昌大学')
            ->assertSee('南昌示例科技有限公司')
            ->assertSee('fi-ta-toggle');
    }
}
