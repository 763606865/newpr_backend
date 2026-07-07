<?php

namespace Tests\Feature;

use App\Enums\CompanyNatureType;
use App\Enums\CompanyScaleType;
use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJobAuditStatus;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Job;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\Rc\SchoolActivityJob;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

        $startTime = now()->addMonth()->setTime(9, 0, 0);
        $endTime = now()->addMonth()->setTime(17, 0, 0);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_hot' => true,
        ]);

        $company = Company::query()->create([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-CMS-ACT-001',
            'title' => '校园宣讲岗位',
            'status' => 1,
            'published_at' => now(),
        ]);

        $companyApplication = SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
        ]);

        SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'school_activity_company_id' => $companyApplication->id,
            'job_id' => $job->id,
        ]);

        SchoolActivityBooth::query()->create([
            'activity_id' => $activity->id,
            'booth_id' => 1,
            'booth_area_code' => 'A',
            'booth_area_name' => 'A 区',
            'booth_no' => 'A-01',
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
            ->assertJsonPath('data.data.0.company_applications_count', 1)
            ->assertJsonPath('data.data.0.jobs_count', 1)
            ->assertJsonPath('data.data.0.activity_booths_count', 1)
            ->assertJsonMissingPath('data.data.0.description');

        $this->getJson('/cms/school-activities?city_code=110100&type=2&start_time='.$startTime->toDateTimeString().'&end_time='.$endTime->toDateTimeString())
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $activity->id);
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
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->twice()
            ->andReturnUsing(static fn (string $path): string => 'https://cdn.example.com/'.$path);

        Storage::shouldReceive('disk')
            ->twice()
            ->with('oss')
            ->andReturn($disk);

        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);

        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'short_name' => '北大',
            'logo' => 'uploads/schools/logo.png',
        ]);

        $startTime = now()->addMonth()->setSecond(0);
        $endTime = now()->addMonths(2)->setSecond(0);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'description' => '<p>活动详情</p>',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => $school->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $company = Company::query()->create([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA0000000Z',
        ]);

        CompanyProfile::query()->create([
            'company_id' => $company->id,
            'short_name' => '示例科技',
            'logo' => 'uploads/companies/logo.png',
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-CMS-ACT-002',
            'title' => '详情校园岗位',
            'status' => 1,
            'published_at' => now(),
        ]);

        $companyApplication = SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'apply_status' => RcSchoolActivityApplyStatus::Approved,
        ]);

        SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'school_activity_company_id' => $companyApplication->id,
            'job_id' => $job->id,
        ]);

        SchoolActivityBooth::query()->create([
            'activity_id' => $activity->id,
            'booth_id' => 2,
            'booth_area_code' => 'B',
            'booth_area_name' => 'B 区',
            'booth_no' => 'B-01',
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
            ->assertJsonPath('data.company_applications_count', 1)
            ->assertJsonPath('data.jobs_count', 1)
            ->assertJsonPath('data.activity_booths_count', 1)
            ->assertJsonPath('data.schools.0.name', '北京大学')
            ->assertJsonPath('data.schools.0.display_name', '北大')
            ->assertJsonPath('data.schools.0.display_logo', 'https://cdn.example.com/uploads/schools/logo.png')
            ->assertJsonPath('data.companies.0.id', $company->id)
            ->assertJsonPath('data.companies.0.display_name', '示例科技')
            ->assertJsonPath('data.companies.0.display_logo', 'https://cdn.example.com/uploads/companies/logo.png')
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

    public function test_get_companies_returns_approved_companies_with_profiles_and_approved_jobs(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '企业参会活动',
            'city_code' => '110100',
            'status' => RcSchoolActivityStatus::Published,
        ]);

        $companyA = Company::query()->create([
            'name' => '企业甲',
            'credit_code' => '91360100MA0000001A',
        ]);

        CompanyProfile::query()->create([
            'company_id' => $companyA->id,
            'short_name' => '甲公司',
            'logo' => 'uploads/company-a.png',
            'scale_type' => CompanyScaleType::Under20,
            'nature_type' => CompanyNatureType::Private,
        ]);

        $companyB = Company::query()->create([
            'name' => '企业乙',
            'credit_code' => '91360100MA0000001B',
        ]);

        CompanyProfile::query()->create([
            'company_id' => $companyB->id,
            'short_name' => '乙公司',
            'logo' => 'uploads/company-b.png',
            'scale_type' => CompanyScaleType::From100To499,
            'nature_type' => CompanyNatureType::StateOwned,
        ]);

        $companyAApplication = SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $companyA->id,
            'apply_status' => RcSchoolActivityApplyStatus::Approved,
            'apply_at' => now()->subMinutes(5),
        ]);

        $companyBApplication = SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $companyB->id,
            'apply_status' => RcSchoolActivityApplyStatus::Approved,
            'apply_at' => now(),
        ]);

        $approvedJobA = Job::query()->create([
            'company_id' => $companyA->id,
            'code' => 'JOB-COMPANY-A-APPROVED',
            'title' => '甲公司岗位A',
            'status' => 1,
            'published_at' => now(),
        ]);

        $pendingJobA = Job::query()->create([
            'company_id' => $companyA->id,
            'code' => 'JOB-COMPANY-A-PENDING',
            'title' => '甲公司岗位B',
            'status' => 1,
            'published_at' => now(),
        ]);

        $approvedJobB = Job::query()->create([
            'company_id' => $companyB->id,
            'code' => 'JOB-COMPANY-B-APPROVED',
            'title' => '乙公司岗位A',
            'status' => 1,
            'published_at' => now(),
        ]);

        SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $companyA->id,
            'school_activity_company_id' => $companyAApplication->id,
            'job_id' => $approvedJobA->id,
            'audit_status' => RcSchoolActivityJobAuditStatus::Approved,
        ]);

        SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $companyA->id,
            'school_activity_company_id' => $companyAApplication->id,
            'job_id' => $pendingJobA->id,
            'audit_status' => RcSchoolActivityJobAuditStatus::Pending,
        ]);

        SchoolActivityJob::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $companyB->id,
            'school_activity_company_id' => $companyBApplication->id,
            'job_id' => $approvedJobB->id,
            'audit_status' => RcSchoolActivityJobAuditStatus::Approved,
        ]);

        $this->getJson('/cms/school-activities/'.$activity->id.'/companies?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.per_page', 1)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.company.id', $companyB->id)
            ->assertJsonPath('data.data.0.company.profile.short_name', '乙公司')
            ->assertJsonPath('data.data.0.company.profile.scale_type', CompanyScaleType::From100To499->value)
            ->assertJsonPath('data.data.0.activity_jobs.0.job.title', '乙公司岗位A')
            ->assertJsonCount(1, 'data.data.0.activity_jobs');

        $this->getJson('/cms/school-activities/'.$activity->id.'/companies?page=2&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.company.id', $companyA->id)
            ->assertJsonPath('data.data.0.activity_jobs.0.job.title', '甲公司岗位A')
            ->assertJsonCount(1, 'data.data.0.activity_jobs');

        $this->getJson('/cms/school-activities/'.$activity->id.'/companies?scale_type='.CompanyScaleType::Under20->value)
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.company.id', $companyA->id);

        $this->getJson('/cms/school-activities/'.$activity->id.'/companies?nature_type='.CompanyNatureType::StateOwned->value)
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.company.id', $companyB->id);
    }

    public function test_index_returns_validation_error_for_invalid_type(): void
    {
        $this->getJson('/cms/school-activities?type=99')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }
}
