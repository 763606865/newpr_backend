<?php

namespace Tests\Unit\Services;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolBoothStatus;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\School;
use App\Services\RcSchoolActivityBoothService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RcSchoolActivityBoothServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_for_activity_generates_booths_from_template_areas(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $booth = SchoolBooth::query()->create([
            'school_code' => $school->school_code,
            'name' => '体育馆',
            'status' => RcSchoolBoothStatus::Enabled,
        ]);
        SchoolBoothArea::query()->create([
            'booth_id' => $booth->id,
            'code' => 'A',
            'name' => 'A 区',
            'start_no' => 1,
            'end_no' => 3,
            'total_booth_count' => 3,
        ]);
        $activity = SchoolActivity::query()->create([
            'title' => '春季双选会',
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'status' => RcSchoolActivityStatus::Draft,
        ]);

        $created = RcSchoolActivityBoothService::make()->syncForActivity($activity, $school, $booth->id);

        $this->assertCount(3, $created);
        $this->assertSame(['A-01', 'A-02', 'A-03'], $created->pluck('booth_no')->all());
        $this->assertSame($booth->id, $activity->refresh()->booth_id);
        $this->assertDatabaseHas('rc_school_activities', [
            'id' => $activity->id,
            'booth_id' => $booth->id,
        ]);
        $this->assertDatabaseCount('rc_school_activity_booths', 3);
    }

    public function test_assert_booth_config_editable_rejects_published_activity(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '已发布活动',
            'status' => RcSchoolActivityStatus::Published,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活动已发布或已结束，不可修改展位配置。');

        RcSchoolActivityBoothService::make()->assertBoothConfigEditable($activity);
    }
}
