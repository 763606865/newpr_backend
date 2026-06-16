<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Filament\Resources\Rc\Offers\Pages\ListOffers;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\Offer;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class OffersListTableTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function offerPermissions(): array
    {
        return [
            'ViewAny:Offer',
            'View:Offer',
            'Create:Offer',
            'Update:Offer',
            'Delete:Offer',
            'DeleteAny:Offer',
            'Restore:Offer',
            'ForceDelete:Offer',
            'ForceDeleteAny:Offer',
            'RestoreAny:Offer',
        ];
    }

    public function test_offers_list_displays_company_and_receive_user_names(): void
    {
        $this->actingAsFilamentAdmin($this->offerPermissions());

        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $user = User::factory()->create([
            'name' => '应聘者张三',
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-OFFER-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '应聘者张三',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $application = Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $job->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'applied_at' => now(),
        ]);

        Offer::query()->create([
            'receive_user_id' => $user->id,
            'company_id' => $company->id,
            'application_id' => $application->id,
            'offer_no' => 'OFFER-20260612-001',
            'salary' => 15000,
        ]);

        Livewire::test(ListOffers::class)
            ->assertSuccessful()
            ->assertSee('南昌示例科技有限公司')
            ->assertSee('应聘者张三');
    }
}
