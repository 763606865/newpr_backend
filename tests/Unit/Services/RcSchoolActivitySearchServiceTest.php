<?php

namespace Tests\Unit\Services;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use App\Services\RcSchoolActivitySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RcSchoolActivitySearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_available_finds_published_open_activities_by_keyword(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        $available = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'description' => '欢迎参加北京大学春季双选会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivitySchool::query()->create([
            'activity_id' => $available->id,
            'school_id' => $school->id,
        ]);

        SchoolActivity::query()->create([
            'title' => '2026 草稿双选会',
            'description' => '北京大学草稿',
            'status' => RcSchoolActivityStatus::Draft,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
        ]);

        SchoolActivity::query()->create([
            'title' => '2025 已结束双选会',
            'description' => '北京大学已结束',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_end_date' => Carbon::yesterday(),
        ]);

        $available->searchable();

        $paginator = RcSchoolActivitySearchService::make()->searchAvailable(15, [
            'keyword' => '北京大学',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('2026 春季双选会', $paginator->items()[0]->title);
    }

    public function test_search_for_school_organizer_filters_by_school(): void
    {
        $schoolA = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $schoolB = School::query()->create([
            'school_code' => '4111010002',
            'name' => '清华大学',
        ]);

        $activityA = SchoolActivity::query()->create([
            'title' => '北大春季招聘会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $schoolA->id,
        ]);

        SchoolActivity::query()->create([
            'title' => '清华春季招聘会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $schoolB->id,
        ]);

        $activityA->searchable();

        $paginator = RcSchoolActivitySearchService::make()->searchForSchoolOrganizer($schoolA->id, 15, [
            'keyword' => '春季',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('北大春季招聘会', $paginator->items()[0]->title);
    }
}
