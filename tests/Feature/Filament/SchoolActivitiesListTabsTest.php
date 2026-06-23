<?php

namespace Tests\Feature\Filament;

use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Filament\Resources\Rc\SchoolActivities\Pages\ListSchoolActivities;
use App\Models\Rc\SchoolActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class SchoolActivitiesListTabsTest extends TestCase
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

    public function test_school_activities_list_filters_records_by_type_tab(): void
    {
        $this->actingAsFilamentAdmin($this->schoolActivityPermissions());

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '示例科技宣讲会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::JobFair,
            'title' => '春季招聘会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        Livewire::test(ListSchoolActivities::class)
            ->assertSuccessful()
            ->assertSee('宣讲会')
            ->assertSee('双选会')
            ->assertSee('招聘会')
            ->assertSee('示例科技宣讲会')
            ->assertDontSee('2026 春季双选会')
            ->assertDontSee('春季招聘会');

        Livewire::test(ListSchoolActivities::class)
            ->set('activeTab', (string) RcSchoolActivityType::DualSelection->value)
            ->assertSee('2026 春季双选会')
            ->assertDontSee('示例科技宣讲会')
            ->assertDontSee('春季招聘会');

        Livewire::test(ListSchoolActivities::class)
            ->set('activeTab', (string) RcSchoolActivityType::JobFair->value)
            ->assertSee('春季招聘会')
            ->assertDontSee('示例科技宣讲会')
            ->assertDontSee('2026 春季双选会');
    }
}
