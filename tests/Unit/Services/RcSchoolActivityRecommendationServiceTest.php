<?php

namespace Tests\Unit\Services;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;
use App\Services\RcSchoolActivityRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcSchoolActivityRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommend_grouped_returns_five_items_per_type_and_filters_by_city(): void
    {
        foreach (range(1, 6) as $index) {
            $this->createAvailableActivity(
                RcSchoolActivityType::DualSelection,
                "南昌双选会 {$index}",
                '360100',
                $index,
            );
        }

        $this->createAvailableActivity(
            RcSchoolActivityType::DualSelection,
            '北京双选会',
            '110100',
        );

        $this->createAvailableActivity(
            RcSchoolActivityType::Presentation,
            '南昌宣讲会',
            '360100',
        );

        $this->createAvailableActivity(
            RcSchoolActivityType::JobFair,
            '南昌招聘会',
            '360100',
        );

        $result = RcSchoolActivityRecommendationService::make()->recommendGrouped(
            new SchoolActivityRecommendationContext(cityHint: '360100'),
            5,
        );

        $this->assertCount(5, $result['dual_selections']);
        $this->assertCount(1, $result['presentations']);
        $this->assertCount(1, $result['job_fairs']);
        $this->assertSame('guest_local', $result['criteria']->strategy);
        $this->assertSame('南昌双选会 6', $result['dual_selections'][0]->title);
        $this->assertSame('南昌宣讲会', $result['presentations'][0]->title);
        $this->assertSame('南昌招聘会', $result['job_fairs'][0]->title);
    }

    private function createAvailableActivity(
        RcSchoolActivityType $type,
        string $title,
        ?string $cityCode = null,
        int $sort = 0,
    ): SchoolActivity {
        return SchoolActivity::query()->create([
            'type' => $type,
            'title' => $title,
            'city_code' => $cityCode,
            'sort' => $sort,
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);
    }
}
