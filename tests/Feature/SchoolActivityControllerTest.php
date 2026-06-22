<?php

namespace Tests\Feature;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_published_activities_for_region(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
            'is_hot' => true,
        ]);

        SchoolActivity::query()->create([
            'title' => '草稿活动',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Draft,
        ]);

        SchoolActivity::query()->create([
            'title' => '其他城市活动',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
        ]);

        $this->getJson('/cms/school-activities?city_code=110100&type=2')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $activity->id)
            ->assertJsonPath('data.data.0.title', '2026 春季双选会')
            ->assertJsonPath('data.data.0.type', RcSchoolActivityType::DualSelection->value)
            ->assertJsonPath('data.data.0.organizer_name', '北京大学')
            ->assertJsonMissingPath('data.data.0.description');
    }

    public function test_index_supports_types_and_organizer_type_filters(): void
    {
        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '宣讲会 A',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::JobFair,
            'title' => '招聘会 B',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::Company,
        ]);

        $this->getJson('/cms/school-activities?types=0,1&organizer_types=school')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '宣讲会 A');
    }

    public function test_show_returns_published_activity_detail(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'description' => '<p>活动详情</p>',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
        ]);

        SchoolActivitySchool::query()->create([
            'activity_id' => $activity->id,
            'school_id' => $school->id,
        ]);

        $this->getJson('/cms/school-activities/'.$activity->id.'?city_code=110100')
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id)
            ->assertJsonPath('data.description', '<p>活动详情</p>')
            ->assertJsonPath('data.organizer_name', '北京大学')
            ->assertJsonPath('data.schools.0.name', '北京大学')
            ->assertJsonMissingPath('data.invite_code');
    }

    public function test_show_returns_not_found_for_draft_or_region_mismatch(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '草稿活动',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Draft,
        ]);

        $this->getJson('/cms/school-activities/'.$activity->id)
            ->assertNotFound();

        $published = SchoolActivity::query()->create([
            'title' => '北京活动',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
        ]);

        $this->getJson('/cms/school-activities/'.$published->id.'?city_code=360100')
            ->assertNotFound();
    }

    public function test_index_returns_validation_error_for_invalid_type(): void
    {
        $this->getJson('/cms/school-activities?type=99')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }
}
