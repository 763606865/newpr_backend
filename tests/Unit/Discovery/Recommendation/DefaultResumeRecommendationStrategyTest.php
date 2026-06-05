<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\Strategies\DefaultResumeRecommendationStrategy;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultResumeRecommendationStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_criteria_returns_default_strategy_without_filters(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $criteria = (new DefaultResumeRecommendationStrategy)->criteria(
            new ResumeRecommendationContext(
                user: $user,
                company: $company,
            ),
        );

        $this->assertSame('default', $criteria->strategy);
        $this->assertSame([], $criteria->searchFilters);
        $this->assertSame($company->id, $criteria->meta['company_id']);
    }
}
