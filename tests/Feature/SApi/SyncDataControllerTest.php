<?php

namespace Tests\Feature\SApi;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Enums\UserGender;
use App\Enums\UserStatus;
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

class SyncDataControllerTest extends TestCase
{
    use InteractsWithSApiSignatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_jobs_index_requires_signature(): void
    {
        $this->getJson('/sapi/jobs')->assertUnauthorized();
    }

    public function test_jobs_index_returns_paginated_jobs(): void
    {
        $client = Client::factory()->create();
        $company = $this->createCompany();
        $this->createJob($company, [
            'code' => 'JOB-DRAFT',
            'title' => '草稿职位',
            'status' => RcJobStatus::Draft,
        ]);
        $included = $this->createJob($company, [
            'title' => '后端工程师',
            'status' => RcJobStatus::Published,
        ]);

        $response = $this->signedGet($client, '/sapi/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonPath('data.data.0.company.name', $company->name)
            ->assertJsonMissingPath('data.data.0.password');
    }

    public function test_jobs_index_filters_by_status_and_created_at(): void
    {
        $client = Client::factory()->create();
        $company = $this->createCompany();
        $published = $this->createJob($company, [
            'code' => 'JOB-PUBLISHED',
            'status' => RcJobStatus::Published,
            'created_at' => '2026-06-02 10:00:00',
        ]);
        $this->createJob($company, [
            'code' => 'JOB-DRAFT-2',
            'status' => RcJobStatus::Draft,
            'created_at' => '2026-06-02 10:00:00',
        ]);
        $this->createJob($company, [
            'code' => 'JOB-OLD',
            'status' => RcJobStatus::Published,
            'created_at' => '2026-06-05 10:00:00',
        ]);

        $response = $this->signedGet($client, '/sapi/jobs', [
            'status' => RcJobStatus::Published->value,
            'created_from' => '2026-06-01 00:00:00',
            'created_to' => '2026-06-03 23:59:59',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $published->id);
    }

    public function test_resumes_index_returns_paginated_resumes(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $included = $this->createResume($user, [
            'title' => '我的简历',
            'status' => RcResumeStatus::Normal,
        ]);

        $response = $this->signedGet($client, '/sapi/resumes');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonPath('data.data.0.user_id', $user->id)
            ->assertJsonPath('data.data.0.title', '我的简历');
    }

    public function test_resumes_index_filters_by_user_id(): void
    {
        $client = Client::factory()->create();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $included = $this->createResume($targetUser, ['title' => '目标简历']);
        $this->createResume($otherUser, ['title' => '其他简历']);

        $response = $this->signedGet($client, '/sapi/resumes', [
            'user_id' => $targetUser->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id);
    }

    public function test_companies_index_returns_paginated_companies(): void
    {
        $client = Client::factory()->create();
        $included = $this->createCompany(['name' => '示例企业']);

        $response = $this->signedGet($client, '/sapi/companies');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonPath('data.data.0.name', '示例企业');
    }

    public function test_companies_index_filters_by_status(): void
    {
        $client = Client::factory()->create();
        $enabled = $this->createCompany([
            'name' => '启用企业',
            'credit_code' => '91360100MA0000001X',
            'status' => CompanyStatus::Enabled,
        ]);
        $this->createCompany([
            'name' => '禁用企业',
            'credit_code' => '91360100MA0000002X',
            'status' => CompanyStatus::Disabled,
        ]);

        $response = $this->signedGet($client, '/sapi/companies', [
            'status' => CompanyStatus::Enabled->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $enabled->id);
    }

    public function test_users_index_returns_users_without_password(): void
    {
        $client = Client::factory()->create();
        $included = User::factory()->create([
            'name' => '张三',
            'status' => UserStatus::Active->value,
            'gender' => UserGender::Male,
        ]);

        $response = $this->signedGet($client, '/sapi/users');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonPath('data.data.0.name', '张三')
            ->assertJsonPath('data.data.0.uuid', $included->uuid)
            ->assertJsonMissingPath('data.data.0.password');
    }

    public function test_users_index_filters_by_updated_at_range(): void
    {
        $client = Client::factory()->create();
        $included = User::factory()->create(['name' => '范围内用户']);
        $included->forceFill([
            'updated_at' => Carbon::parse('2026-06-02 12:00:00'),
        ])->saveQuietly();

        $excluded = User::factory()->create(['name' => '范围外用户']);
        $excluded->forceFill([
            'updated_at' => Carbon::parse('2026-06-05 12:00:00'),
        ])->saveQuietly();

        $response = $this->signedGet($client, '/sapi/users', [
            'updated_from' => '2026-06-01 00:00:00',
            'updated_to' => '2026-06-03 23:59:59',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id);
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
