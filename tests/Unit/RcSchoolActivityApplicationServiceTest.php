<?php

namespace Tests\Unit;

use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Services\RcSchoolActivityApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RcSchoolActivityApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_organizer_company_application_creates_approved_record(): void
    {
        $company = Company::query()->create([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
        ]);

        $activity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '企业宣讲会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::Company,
            'organizer_id' => $company->id,
        ]);

        $application = RcSchoolActivityApplicationService::make()->ensureOrganizerCompanyApplication($activity);

        $this->assertSame($company->id, $application->company_id);
        $this->assertSame(RcSchoolActivityJoinSource::Organizer, $application->join_source);
        $this->assertSame(RcSchoolActivityApplyStatus::Approved, $application->apply_status);

        $again = RcSchoolActivityApplicationService::make()->ensureOrganizerCompanyApplication($activity->refresh());

        $this->assertTrue($again->is($application));
    }

    public function test_ensure_organizer_company_application_rejects_non_company_organizer(): void
    {
        $activity = SchoolActivity::query()->create([
            'title' => '学校双选会',
            'organizer_type' => RcSchoolActivityOrganizerType::School,
            'organizer_id' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('仅企业主办的活动可同步主办方参会记录。');

        RcSchoolActivityApplicationService::make()->ensureOrganizerCompanyApplication($activity);
    }
}
