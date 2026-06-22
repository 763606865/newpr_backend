<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteriaResolver;
use App\Enums\RcEmploymentType;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolActivityRecommendationCriteriaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_context_uses_guest_local_strategy(): void
    {
        $criteria = (new SchoolActivityRecommendationCriteriaResolver)->resolve(
            new SchoolActivityRecommendationContext(cityHint: '360100'),
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
    }

    public function test_user_with_intention_uses_intention_strategy(): void
    {
        $user = User::factory()->create();
        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'resume_no' => 'RC202601010001',
            'title' => '默认简历',
            'full_name' => '张三',
            'phone' => '13800138001',
            'email' => 'zhangsan@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'expected_city_code' => '440300',
            'employment_type' => RcEmploymentType::FullTime,
        ]);

        $criteria = (new SchoolActivityRecommendationCriteriaResolver)->resolve(
            new SchoolActivityRecommendationContext(user: $user),
        );

        $this->assertSame('intention', $criteria->strategy);
        $this->assertSame('440300', $criteria->searchFilters['city_code']);
    }
}
