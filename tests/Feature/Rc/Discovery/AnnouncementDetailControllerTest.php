<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CmsPublishStatus;
use App\Models\Rc\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementDetailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_guest_can_view_published_announcement_detail(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '2026 届校园招聘公告',
            'publisher_name' => '示例集团',
            'summary' => '招聘公告摘要',
            'content' => '<p>招聘公告正文</p>',
            'link_url' => 'https://example.com/announcement',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'expired_at' => now()->addMonth(),
        ]);

        $response = $this->getJson('/rc/talent/announcements/'.$announcement->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.title', '2026 届校园招聘公告')
            ->assertJsonPath('data.summary', '招聘公告摘要')
            ->assertJsonPath('data.content', '<p>招聘公告正文</p>');
    }

    public function test_draft_announcement_returns_not_found(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '草稿公告',
            'content' => '草稿正文',
            'status' => CmsPublishStatus::Draft,
        ]);

        $this->getJson('/rc/talent/announcements/'.$announcement->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '招聘公告不存在或已下架。');
    }

    public function test_expired_announcement_returns_not_found(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '已失效公告',
            'content' => '已失效正文',
            'status' => CmsPublishStatus::Published,
            'published_at' => now()->subMonth(),
            'expired_at' => now()->subDay(),
        ]);

        $this->getJson('/rc/talent/announcements/'.$announcement->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '招聘公告不存在或已下架。');
    }
}
