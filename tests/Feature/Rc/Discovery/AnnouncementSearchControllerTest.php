<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Models\Rc\Announcement;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/announcements');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_index_returns_matching_announcements_for_job_seeker(): void
    {
        $jobSeeker = $this->createJobSeekerContext();

        Announcement::query()->create([
            'title' => '中粮集团2026届校园招聘',
            'publisher_name' => '中粮集团有限公司',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/cofco',
            'employment_types' => [RcJobEmploymentType::Campus->value],
            'graduation_years' => [2026],
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addMonth(),
        ]);

        Announcement::query()->create([
            'title' => '草稿公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/draft',
            'status' => CmsPublishStatus::Draft,
        ]);

        $response = $this
            ->actingAs($jobSeeker, 'rc')
            ->getJson('/rc/talent/announcements?publisher_type='.CmsAnnouncementPublisherType::CentralEnterprise->value);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '中粮集团2026届校园招聘')
            ->assertJsonPath('data.data.0.publisher_name', '中粮集团有限公司')
            ->assertJsonPath('data.data.0.link_url', 'https://example.com/cofco')
            ->assertJsonPath('data.data.0.apply_status', '正在报名');
    }

    private function createJobSeekerContext(): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }
}
