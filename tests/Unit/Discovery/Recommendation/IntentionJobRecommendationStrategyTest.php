<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\Strategies\IntentionJobRecommendationStrategy;
use App\Enums\RcEmploymentType;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntentionJobRecommendationStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_intention_criteria_from_primary_resume_intention(): void
    {
        $user = User::factory()->create();

        Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'sort' => 1,
        ]);

        $position = Position::query()->where('code', 'backend-developer')->firstOrFail();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '张三',
            'phone' => '13800138000',
            'email' => 'zhangsan@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'expected_city_code' => '360100',
            'expected_industry_codes' => ['A01'],
            'expected_position_id' => $position->id,
            'employment_type' => RcEmploymentType::FullTime,
            'salary_min' => 12000,
            'salary_max' => 20000,
        ]);

        $strategy = new IntentionJobRecommendationStrategy;

        $this->assertTrue($strategy->supports(new JobRecommendationContext(user: $user)));

        $criteria = $strategy->criteria(new JobRecommendationContext(user: $user));

        $this->assertSame('intention', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertSame('backend-developer', $criteria->searchFilters['position_code']);
        $this->assertSame(RcEmploymentType::FullTime->value, $criteria->searchFilters['employment_type']);
        $this->assertSame(12000.0, $criteria->searchFilters['salary_min']);
        $this->assertSame(20000.0, $criteria->searchFilters['salary_max']);
        $this->assertSame(['A01'], $criteria->meta['industry_codes']);
        $this->assertTrue($criteria->meta['industry_filter_pending']);
    }

    public function test_it_does_not_support_user_without_meaningful_intention(): void
    {
        $user = User::factory()->create();

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '李四',
            'phone' => '13800138001',
            'email' => 'lisi@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $strategy = new IntentionJobRecommendationStrategy;

        $this->assertFalse($strategy->supports(new JobRecommendationContext(user: $user)));
    }
}
