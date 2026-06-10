<?php

namespace Tests\Feature\SApi;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Enums\UserGender;
use App\Models\Cms\Announcement;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Resume;
use App\Models\SApi\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithSApiSignatures;
use Tests\TestCase;

class SApiPaginationConfigTest extends TestCase
{
    use InteractsWithSApiSignatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        config(['sapi.pagination_enabled' => false]);
    }

    public function test_jobs_index_returns_full_list_when_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $company = $this->createCompany();
        $first = $this->createJob($company, ['code' => 'JOB-1', 'title' => '职位一']);
        $second = $this->createJob($company, ['code' => 'JOB-2', 'title' => '职位二']);

        $response = $this->signedGet($client, '/sapi/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);
    }

    public function test_jobs_index_can_request_full_list_via_query_parameter_when_global_pagination_enabled(): void
    {
        config(['sapi.pagination_enabled' => true]);

        $client = Client::factory()->create();
        $company = $this->createCompany();
        $this->createJob($company, ['code' => 'JOB-1', 'title' => '职位一']);
        $this->createJob($company, ['code' => 'JOB-2', 'title' => '职位二']);

        $response = $this->signedGet($client, '/sapi/jobs', [
            'pagination_enabled' => 0,
        ]);

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(2, 'data');
    }

    public function test_jobs_index_can_request_pagination_via_query_parameter_when_global_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $company = $this->createCompany();
        $this->createJob($company, ['code' => 'JOB-1', 'title' => '职位一']);
        $this->createJob($company, ['code' => 'JOB-2', 'title' => '职位二']);

        $response = $this->signedGet($client, '/sapi/jobs', [
            'pagination_enabled' => 1,
            'per_page' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_resumes_index_returns_full_list_when_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $response = $this->signedGet($client, '/sapi/resumes');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $resume->id);
    }

    public function test_companies_index_returns_full_list_when_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $company = $this->createCompany(['name' => '全量企业']);

        $response = $this->signedGet($client, '/sapi/companies');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $company->id);
    }

    public function test_users_index_returns_full_list_when_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create(['name' => '李四']);

        $response = $this->signedGet($client, '/sapi/users');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user->id);
    }

    public function test_announcements_index_returns_full_list_when_pagination_disabled(): void
    {
        $client = Client::factory()->create();
        $announcement = Announcement::query()->create([
            'title' => '全量公告',
            'type' => CmsAnnouncementType::SelfPublished,
            'status' => CmsPublishStatus::Published,
            'published_at' => Carbon::parse('2026-06-02 10:00:00'),
            'created_at' => Carbon::parse('2026-06-02 10:00:00'),
            'updated_at' => Carbon::parse('2026-06-02 10:00:00'),
        ]);

        $response = $this->signedGet($client, '/sapi/announcements');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.total')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $announcement->id);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function signedGet(Client $client, string $path, array $query = []): TestResponse
    {
        $uri = $query === [] ? $path : $path.'?'.http_build_query($query);

        return $this->withHeaders(
            $this->sapiSignatureHeaders($client, 'GET', $uri, $query),
        )->get($uri);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompany(array $overrides = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '测试企业',
            'credit_code' => '91360100MA'.strtoupper(substr(uniqid(), -8)),
            'status' => CompanyStatus::Enabled,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createJob(Company $company, array $overrides = []): Job
    {
        $createdAt = isset($overrides['created_at'])
            ? Carbon::parse($overrides['created_at'])
            : Carbon::parse('2026-06-02 09:00:00');
        unset($overrides['created_at']);

        $job = Job::query()->create(array_merge([
            'company_id' => $company->id,
            'code' => 'JOB-'.strtoupper(substr(uniqid(), -6)),
            'title' => '测试职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '职位描述',
            'status' => RcJobStatus::Published,
        ], $overrides));

        $job->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $job->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createResume(User $user, array $overrides = []): Resume
    {
        return Resume::query()->create(array_merge([
            'user_id' => $user->id,
            'resume_no' => 'RC'.now()->format('YmdHis').strtoupper(substr(uniqid(), -4)),
            'title' => '测试简历',
            'full_name' => $user->name,
            'gender' => UserGender::Unknown,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => RcResumeStatus::Normal,
        ], $overrides));
    }
}
