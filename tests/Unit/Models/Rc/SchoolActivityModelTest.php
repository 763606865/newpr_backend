<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJobAuditStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Enums\RcSchoolBoothStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\Rc\SchoolActivityJob;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\School;
use App\Support\SchoolActivityInviteCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolActivityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_activity_relationships_and_organizer_morph(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
        ]);

        SchoolActivitySchool::query()->create([
            'activity_id' => $activity->id,
            'school_id' => $school->id,
        ]);

        $activity->load(['organizer', 'schools', 'schoolLinks.school']);

        $this->assertTrue($activity->organizer->is($school));
        $this->assertCount(1, $activity->schools);
        $this->assertSame('北京大学', $activity->schools->first()?->name);
        $this->assertTrue($activity->schoolLinks->first()?->school->is($school));
    }

    public function test_company_application_links_activity_booth_and_jobs(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $company = Company::query()->create([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
        ]);
        $activity = SchoolActivity::query()->create([
            'title' => '春季招聘会',
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
        ]);
        SchoolActivitySchool::query()->create([
            'activity_id' => $activity->id,
            'school_id' => $school->id,
        ]);
        $boothTemplate = SchoolBooth::query()->create([
            'school_code' => $school->school_code,
            'name' => '体育馆 A 区',
            'status' => RcSchoolBoothStatus::Enabled,
        ]);
        $area = SchoolBoothArea::query()->create([
            'booth_id' => $boothTemplate->id,
            'name' => 'A 区',
            'start_no' => 1,
            'end_no' => 10,
        ]);
        $activityBooth = SchoolActivityBooth::query()->create([
            'activity_id' => $activity->id,
            'booth_id' => $boothTemplate->id,
            'school_id' => $school->id,
            'booth_area_id' => $area->id,
            'booth_area_code' => 'A',
            'booth_area_name' => 'A 区',
            'booth_no' => 'A-01',
            'status' => RcSchoolBoothStatus::Enabled,
        ]);
        $application = SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'activity_booth_id' => $activityBooth->id,
            'join_source' => RcSchoolActivityJoinSource::SchoolInvite,
        ]);
        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB001',
            'title' => '后端工程师',
        ]);
        $activityJob = SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'school_activity_company_id' => $application->id,
            'job_id' => $job->id,
            'audit_status' => RcSchoolActivityJobAuditStatus::Approved,
        ]);

        $application->load(['activity', 'company', 'activityBooth.boothArea', 'activityJobs.job']);
        $activityBooth->load(['booth.areas', 'companyApplications']);
        $school->load(['booths.areas', 'activities', 'activityBooths']);
        $company->load(['schoolActivityCompanies.activity', 'schoolActivityBooths']);
        $job->load('schoolActivityJobs.activity');

        $this->assertTrue($application->activity->is($activity));
        $this->assertTrue($application->company->is($company));
        $this->assertTrue($application->activityBooth?->is($activityBooth));
        $this->assertSame('A 区', $application->activityBooth?->boothArea?->name);
        $this->assertTrue($application->activityJobs->first()?->job->is($job));
        $this->assertSame(RcSchoolActivityApplyStatus::Pending, $application->apply_status);
        $this->assertNotNull($application->apply_at);
        $this->assertCount(1, $activityBooth->companyApplications);
        $this->assertCount(1, $school->booths);
        $this->assertCount(1, $school->activities);
        $this->assertCount(1, $company->schoolActivityCompanies);
        $this->assertTrue($job->schoolActivityJobs->first()?->activity->is($activity));
        $this->assertTrue($activityJob->companyApplication->is($application));
    }

    public function test_scopes_filter_activity_and_booth_records(): void
    {
        SchoolActivity::query()->create([
            'title' => '草稿活动',
            'type' => RcSchoolActivityType::Presentation,
            'status' => RcSchoolActivityStatus::Draft,
        ]);
        SchoolActivity::query()->create([
            'title' => '热门活动',
            'status' => RcSchoolActivityStatus::Published,
            'is_hot' => true,
        ]);

        $this->assertSame(1, SchoolActivity::query()->published()->count());
        $this->assertSame(1, SchoolActivity::query()->hot()->count());
        $this->assertSame(1, SchoolActivity::query()->ofType(RcSchoolActivityType::JobFair)->count());

        $activity = SchoolActivity::query()->first();
        SchoolActivityBooth::query()->create([
            'activity_id' => $activity->id,
            'booth_id' => SchoolBooth::query()->create([
                'name' => '主馆',
            ])->id,
            'booth_area_code' => 'A',
            'booth_area_name' => 'A 区',
            'booth_no' => 'A-01',
            'status' => RcSchoolBoothStatus::Enabled,
        ]);
        SchoolActivityBooth::query()->create([
            'activity_id' => $activity->id,
            'booth_id' => SchoolBooth::query()->create([
                'name' => '副馆',
            ])->id,
            'booth_area_code' => 'B',
            'booth_area_name' => 'B 区',
            'booth_no' => 'B-01',
            'company_id' => Company::query()->create([
                'name' => '示例企业',
                'credit_code' => '91360100MA0000000A',
            ])->id,
            'status' => RcSchoolBoothStatus::Enabled,
        ]);

        $this->assertSame(1, SchoolActivityBooth::query()->forActivity($activity->id)->available()->count());
        $this->assertSame(2, SchoolActivityBooth::query()->enabled()->count());
    }

    public function test_invite_code_accessor_encodes_and_decodes_activity_id(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '邀请测试活动',
        ]);

        $inviteCode = $activity->invite_code;

        $this->assertNotSame((string) $activity->id, $inviteCode);
        $this->assertSame($activity->id, SchoolActivityInviteCode::decode($inviteCode));
    }

    public function test_activity_belongs_to_booth_template(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $booth = SchoolBooth::query()->create([
            'school_code' => $school->school_code,
            'name' => '体育馆',
        ]);
        $activity = SchoolActivity::query()->create([
            'title' => '春季双选会',
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'booth_id' => $booth->id,
        ]);

        $activity->load(['booth', 'booth.schoolActivities']);
        $booth->load('schoolActivities');

        $this->assertTrue($activity->booth?->is($booth));
        $this->assertTrue($booth->schoolActivities->first()?->is($activity));
    }
}
