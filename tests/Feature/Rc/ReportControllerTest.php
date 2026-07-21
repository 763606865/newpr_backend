<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcReportReasonType;
use App\Enums\RcReportStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_user_can_report_job(): void
    {
        $user = $this->createUserWithIdentity();
        $job = $this->createJob();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/reports', [
                'reportable_type' => 'job',
                'reportable_id' => $job->id,
                'reason_type' => RcReportReasonType::FalseInformation->value,
                'reason' => '虚假职位',
                'description' => '岗位描述与实际不符。',
                'attachments' => ['reports/job-evidence.png'],
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.report.status', RcReportStatus::Pending->value)
            ->assertJsonPath('data.report.reportable_type', 'job')
            ->assertJsonPath('data.report.reportable_id', $job->id);

        $this->assertDatabaseHas('rc_reports', [
            'user_id' => $user->id,
            'reportable_type' => 'job',
            'reportable_id' => $job->id,
            'reason_type' => RcReportReasonType::FalseInformation->value,
            'reason' => '虚假职位',
            'status' => RcReportStatus::Pending->value,
        ]);
    }

    public function test_user_can_report_company(): void
    {
        $user = $this->createUserWithIdentity();
        $company = $this->createCompany('91360100MAREPORT02');

        $this->actingAs($user, 'rc')
            ->postJson('/rc/reports', [
                'reportable_type' => 'company',
                'reportable_id' => $company->id,
                'reason_type' => RcReportReasonType::Fraud->value,
                'reason' => '企业信息异常',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.report.reportable_type', 'company')
            ->assertJsonPath('data.report.reportable_id', $company->id);

        $this->assertDatabaseHas('rc_reports', [
            'user_id' => $user->id,
            'reportable_type' => 'company',
            'reportable_id' => $company->id,
            'reason_type' => RcReportReasonType::Fraud->value,
        ]);
    }

    public function test_user_can_report_resume(): void
    {
        $user = $this->createUserWithIdentity();
        $resumeOwner = User::factory()->create();
        $resume = Resume::query()->create([
            'user_id' => $resumeOwner->id,
            'full_name' => '被举报求职者',
            'phone' => '13800138000',
            'email' => 'reported@example.com',
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/reports', [
                'reportable_type' => 'resume',
                'reportable_id' => $resume->id,
                'reason_type' => RcReportReasonType::IllegalContent->value,
                'description' => '简历联系方式疑似虚假。',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.report.reportable_type', 'resume')
            ->assertJsonPath('data.report.reportable_id', $resume->id);

        $this->assertDatabaseHas('rc_reports', [
            'user_id' => $user->id,
            'reportable_type' => 'resume',
            'reportable_id' => $resume->id,
            'reason_type' => RcReportReasonType::IllegalContent->value,
        ]);
    }

    public function test_report_requires_current_identity(): void
    {
        $user = User::factory()->create();
        $job = $this->createJob();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/reports', [
                'reportable_type' => 'job',
                'reportable_id' => $job->id,
                'reason_type' => RcReportReasonType::FalseInformation->value,
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先选择用户身份。');
    }

    public function test_report_returns_not_found_for_missing_reportable(): void
    {
        $user = $this->createUserWithIdentity();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/reports', [
                'reportable_type' => 'job',
                'reportable_id' => 999999,
                'reason_type' => RcReportReasonType::FalseInformation->value,
            ])
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '举报对象不存在。');
    }

    private function createUserWithIdentity(): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }

    private function createJob(): Job
    {
        $company = $this->createCompany('91360100MAREPORT01');

        return Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-REPORT-001',
            'title' => '可举报职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);
    }

    private function createCompany(string $creditCode): Company
    {
        return Company::query()->create([
            'name' => '举报测试企业',
            'credit_code' => $creditCode,
            'status' => CompanyStatus::Enabled,
        ]);
    }
}
