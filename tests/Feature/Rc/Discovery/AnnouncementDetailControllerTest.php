<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementType;
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
            'registration_url' => 'https://example.com/register',
            'announcement_type' => RcAnnouncementType::StateOwnedEnterpriseRecruitment,
            'recruitment_count' => 50,
            'attachments' => [
                ['name' => '招聘岗位表.xlsx', 'url' => 'https://example.com/jobs.xlsx'],
            ],
            'extra' => [
                'display' => [
                    'industry' => '能源',
                    'positions_number' => 10,
                    'has_establishment' => false,
                ],
                'cjwl' => ['company_id' => 1001],
            ],
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'expired_at' => now()->addMonth(),
            'read_count' => 5,
        ]);

        $response = $this->getJson('/rc/talent/announcements/'.$announcement->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.title', '2026 届校园招聘公告')
            ->assertJsonPath('data.summary', '招聘公告摘要')
            ->assertJsonPath('data.content', '<p>招聘公告正文</p>')
            ->assertJsonPath('data.announcement_type', RcAnnouncementType::StateOwnedEnterpriseRecruitment->value)
            ->assertJsonPath('data.announcement_type_label', '国央企招聘')
            ->assertJsonPath('data.recruitment_count', 50)
            ->assertJsonPath('data.registration_url', 'https://example.com/register')
            ->assertJsonPath('data.attachments.0.name', '招聘岗位表.xlsx')
            ->assertJsonPath('data.attachments.0.url', 'https://example.com/jobs.xlsx')
            ->assertJsonPath('data.display_fields.industry', '能源')
            ->assertJsonPath('data.display_fields.positions_number', 10)
            ->assertJsonPath('data.display_fields.has_establishment', false)
            ->assertJsonPath('data.read_count', 6)
            ->assertJsonMissingPath('data.extra')
            ->assertJsonMissingPath('data.cjwl');

        $this->assertSame(6, $announcement->refresh()->read_count);
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

        $this->assertSame(0, $announcement->refresh()->read_count);
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

        $this->assertSame(0, $announcement->refresh()->read_count);
    }
}
