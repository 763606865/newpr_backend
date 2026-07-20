<?php

namespace Tests\Feature\Filament;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Filament\Resources\Cms\HomeRecommendations\Pages\CreateHomeRecommendation;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\CompanyProfile;
use App\Models\Rc\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CmsHomeRecommendationFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function homeRecommendationPermissions(): array
    {
        return [
            'ViewAny:HomeRecommendation',
            'View:HomeRecommendation',
            'Create:HomeRecommendation',
            'Update:HomeRecommendation',
            'Delete:HomeRecommendation',
            'DeleteAny:HomeRecommendation',
            'Restore:HomeRecommendation',
            'ForceDelete:HomeRecommendation',
            'ForceDeleteAny:HomeRecommendation',
            'RestoreAny:HomeRecommendation',
        ];
    }

    public function test_create_home_recommendation_for_hot_job(): void
    {
        $this->actingAsFilamentAdmin($this->homeRecommendationPermissions());

        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-FILAMENT-001',
            'title' => 'Filament 热招职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Livewire::test(CreateHomeRecommendation::class)
            ->fillForm([
                'module_type' => CmsHomeRecommendationModuleType::HotJob,
                'job_id' => $job->id,
                'title' => 'Filament 热招职位',
                'status' => CmsStatus::Enabled,
                'sort' => 10,
            ])
            ->call('create')
            ->assertNotified();

        $recommendation = HomeRecommendation::query()->first();

        $this->assertNotNull($recommendation);
        $this->assertSame(CmsHomeRecommendationModuleType::HotJob, $recommendation->module_type);
        $this->assertSame('job', $recommendation->recommendable_type);
        $this->assertSame($job->id, $recommendation->recommendable_id);
    }

    public function test_create_home_recommendation_for_famous_company(): void
    {
        $this->actingAsFilamentAdmin($this->homeRecommendationPermissions());

        $company = Company::query()->create([
            'name' => '名企示例公司',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        CompanyProfile::query()->create([
            'company_id' => $company->id,
            'short_name' => '名企示例',
            'is_brand' => true,
        ]);

        Livewire::test(CreateHomeRecommendation::class)
            ->fillForm([
                'module_type' => CmsHomeRecommendationModuleType::FamousCompany,
                'company_id' => $company->id,
                'status' => CmsStatus::Enabled,
                'sort' => 1,
            ])
            ->call('create')
            ->assertNotified();

        $recommendation = HomeRecommendation::query()->first();

        $this->assertNotNull($recommendation);
        $this->assertSame(CmsHomeRecommendationModuleType::FamousCompany, $recommendation->module_type);
        $this->assertSame('company', $recommendation->recommendable_type);
        $this->assertSame($company->id, $recommendation->recommendable_id);
    }

    public function test_create_home_recommendation_for_campus_hot_job(): void
    {
        $this->actingAsFilamentAdmin($this->homeRecommendationPermissions());

        $company = Company::query()->create([
            'name' => '校招示例科技有限公司',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $campusJob = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-FILAMENT-CAMPUS-001',
            'title' => 'Filament 热门校招职位',
            'employment_type' => RcJobEmploymentType::Campus,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Livewire::test(CreateHomeRecommendation::class)
            ->fillForm([
                'module_type' => CmsHomeRecommendationModuleType::CampusHotJob,
                'campus_job_id' => $campusJob->id,
                'title' => 'Filament 热门校招职位',
                'status' => CmsStatus::Enabled,
                'sort' => 8,
            ])
            ->call('create')
            ->assertNotified();

        $recommendation = HomeRecommendation::query()->first();

        $this->assertNotNull($recommendation);
        $this->assertSame(CmsHomeRecommendationModuleType::CampusHotJob, $recommendation->module_type);
        $this->assertSame('job', $recommendation->recommendable_type);
        $this->assertSame($campusJob->id, $recommendation->recommendable_id);
    }
}
