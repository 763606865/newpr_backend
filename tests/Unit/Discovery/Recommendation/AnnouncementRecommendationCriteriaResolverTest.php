<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteriaResolver;
use App\Enums\RcEducationLevel;
use App\Enums\RcEmploymentType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeIntention;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\RcResumeAggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementRecommendationCriteriaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_intention_strategy_for_user_with_resume_intention(): void
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'resume_no' => 'RES-001',
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'is_fresh_graduate' => 1,
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'school_name' => '示例大学',
            'degree' => RcEducationLevel::Bachelor,
            'start_date' => '2016-09-01',
            'end_date' => '2020-06-30',
        ]);

        RcResumeAggregateService::make()->sync($resume->fresh());

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'employment_type' => RcEmploymentType::Internship,
            'expected_city_code' => '360100',
        ]);

        $criteria = (new AnnouncementRecommendationCriteriaResolver)->resolve(
            new AnnouncementRecommendationContext(user: $user, cityHint: '360100'),
        );

        $this->assertSame('intention', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertSame(RcEmploymentType::Internship->value, $criteria->searchFilters['employment_type']);
        $this->assertSame(RcEducationLevel::Bachelor->value, $criteria->searchFilters['education_level']);
        $this->assertTrue($criteria->searchFilters['apply_open']);
    }

    public function test_resolves_guest_local_strategy_for_guest_with_city_hint(): void
    {
        $criteria = (new AnnouncementRecommendationCriteriaResolver)->resolve(
            new AnnouncementRecommendationContext(cityHint: '360100'),
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertTrue($criteria->searchFilters['apply_open']);
    }
}
