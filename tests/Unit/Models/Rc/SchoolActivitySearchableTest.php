<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SchoolActivitySearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_includes_organizer_and_school_fields(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'description' => '<p>面向2026届毕业生</p>',
            'address' => '体育馆',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonths(2),
            'extra' => [
                'keywords' => ['校招', '双选'],
            ],
        ]);

        SchoolActivitySchool::query()->create([
            'activity_id' => $activity->id,
            'school_id' => $school->id,
        ]);

        $searchable = $activity->fresh(['organizer', 'schools'])->toSearchableArray();

        $this->assertSame('rc_school_activities', $activity->searchableAs());
        $this->assertTrue($activity->shouldBeSearchable());
        $this->assertTrue($activity->isPubliclySearchable());
        $this->assertTrue($activity->isRegisterOpen());
        $this->assertTrue($activity->isAvailableForRecruiter());
        $this->assertSame($activity->id, $searchable['id']);
        $this->assertSame('2026 春季双选会', $searchable['title']);
        $this->assertSame('面向2026届毕业生', $searchable['description']);
        $this->assertSame('北京大学', $searchable['organizer_name']);
        $this->assertSame('北京大学', $searchable['school_names']);
        $this->assertSame('4111010001', $searchable['school_codes']);
        $this->assertSame('校招 双选', $searchable['keywords']);
        $this->assertSame(1, $searchable['is_public']);
        $this->assertSame(1, $searchable['is_register_open']);
        $this->assertSame(1, $searchable['is_available']);
    }

    public function test_draft_or_closed_registration_activity_is_not_available_in_index(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '草稿活动',
            'status' => RcSchoolActivityStatus::Draft,
            'register_end_date' => Carbon::yesterday(),
        ]);

        $searchable = $activity->toSearchableArray();

        $this->assertFalse($activity->isPubliclySearchable());
        $this->assertFalse($activity->isRegisterOpen());
        $this->assertFalse($activity->isAvailableForRecruiter());
        $this->assertSame(0, $searchable['is_public']);
        $this->assertSame(0, $searchable['is_register_open']);
        $this->assertSame(0, $searchable['is_available']);
    }
}
