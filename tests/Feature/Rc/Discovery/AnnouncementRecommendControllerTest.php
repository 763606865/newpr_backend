<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Enums\RcEmploymentType;
use App\Models\Area;
use App\Models\Rc\Announcement;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use App\Services\RcResumeAggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnnouncementRecommendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_guest_can_get_local_open_announcement_recommendations_without_auth(): void
    {
        Carbon::setTestNow('2026-06-30 10:00:00');

        Area::query()->create([
            'name' => '南昌市',
            'code' => '360100',
            'parent_code' => '360000',
            'level' => 2,
            'type' => null,
        ]);

        $local = Announcement::query()->create([
            'title' => '南昌央企招聘',
            'publisher_name' => '江西央企',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/nanchang',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addMonth(),
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        ]);
        $local->syncCityCodes(['360100']);

        Announcement::query()->create([
            'title' => '外地央企招聘',
            'publisher_name' => '外地央企',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/other',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addMonth(),
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        ]);

        $response = $this->getJson('/rc/talent/announcements/recommend?city_code=360100');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.recommendation.strategy', 'guest_local')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '南昌央企招聘')
            ->assertJsonPath('data.data.0.apply_start_at', '2026-06-29 10:00:00');
    }

    public function test_logged_in_user_with_intention_and_education_gets_recommendations(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'is_primary' => 1,
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

        $announcement = Announcement::query()->create([
            'title' => '南昌实习招聘',
            'publisher_name' => '江西央企',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/internship',
            'employment_types' => [RcEmploymentType::Internship->value],
            'education_level' => RcEducationLevel::Bachelor,
            'graduation_years' => [(int) now()->format('Y')],
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addMonth(),
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        ]);
        $announcement->syncCityCodes(['360100']);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/announcements/recommend?city_code=360100');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.recommendation.strategy', 'intention')
            ->assertJsonPath('data.recommendation.applied_filters.education_level', RcEducationLevel::Bachelor->value)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '南昌实习招聘');
    }
}
