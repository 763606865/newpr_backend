<?php

namespace Tests\Unit\Models\Cms;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Announcement;
use App\Models\Cms\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_includes_content_and_tags(): void
    {
        $tag = Tag::query()->create([
            'category' => 'rc',
            'name' => '校招',
            'slug' => 'campus-recruitment',
        ]);

        $announcement = Announcement::query()->create([
            'title' => '2026 春季招聘公告',
            'sub_title' => '央企专场',
            'summary' => '面向应届毕业生',
            'content' => '<p>欢迎投递简历</p>',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $announcement->tags()->sync([$tag->id]);

        $searchable = $announcement->fresh(['tags'])->toSearchableArray();

        $this->assertSame('cms_announcements', $announcement->searchableAs());
        $this->assertTrue($announcement->shouldBeSearchable());
        $this->assertTrue($announcement->isPubliclySearchable());
        $this->assertSame('2026 春季招聘公告', $searchable['title']);
        $this->assertSame('央企专场', $searchable['sub_title']);
        $this->assertSame('欢迎投递简历', $searchable['content']);
        $this->assertSame('校招', $searchable['tag_names']);
        $this->assertSame('campus-recruitment', $searchable['tag_slugs']);
        $this->assertSame('校招 campus-recruitment', $searchable['tags']);
        $this->assertSame([$tag->id], $searchable['tag_ids']);
        $this->assertSame(1, $searchable['is_public']);
    }

    public function test_draft_announcement_is_not_publicly_searchable(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '草稿公告',
            'type' => CmsAnnouncementType::System,
            'status' => CmsPublishStatus::Draft,
        ]);

        $searchable = $announcement->toSearchableArray();

        $this->assertFalse($announcement->isPubliclySearchable());
        $this->assertSame(0, $searchable['is_public']);
    }
}
