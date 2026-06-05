<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteriaResolver;
use App\Enums\RcEmploymentType;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRecommendationCriteriaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_intention_strategy_when_available(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '王五',
            'phone' => '13800138002',
            'email' => 'wangwu@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'expected_city_code' => '440300',
            'employment_type' => RcEmploymentType::FullTime,
        ]);

        $criteria = (new JobRecommendationCriteriaResolver)->resolve(
            new JobRecommendationContext(user: $user, cityHint: '360100'),
        );

        $this->assertSame('intention', $criteria->strategy);
        $this->assertSame('440300', $criteria->searchFilters['city_code']);
    }

    public function test_it_falls_back_to_guest_strategy_for_anonymous_users(): void
    {
        $criteria = (new JobRecommendationCriteriaResolver)->resolve(
            new JobRecommendationContext(cityHint: '360100'),
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertSame(10000, $criteria->searchFilters['salary_min']);
    }
}
