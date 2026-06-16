<?php

namespace Tests\Unit;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Announcement;
use App\Models\Cms\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_name_is_unique_within_category(): void
    {
        Tag::query()->create([
            'category' => 'rc',
            'name' => '全职',
        ]);

        $this->expectException(QueryException::class);

        Tag::query()->create([
            'category' => 'rc',
            'name' => '全职',
        ]);
    }

    public function test_same_tag_name_can_exist_in_different_categories(): void
    {
        Tag::query()->create([
            'category' => 'rc',
            'name' => '全职',
        ]);

        $tag = Tag::query()->create([
            'category' => 'exam',
            'name' => '全职',
        ]);

        $this->assertSame('exam', $tag->category);
    }

    public function test_announcement_can_attach_tags(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '招考公告',
            'type' => CmsAnnouncementType::ExamRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $rcTag = Tag::query()->create([
            'category' => 'rc',
            'name' => '全职',
        ]);
        $examTag = Tag::query()->create([
            'category' => 'exam',
            'name' => '高考',
        ]);

        $announcement->tags()->sync([$rcTag->id, $examTag->id]);

        $announcement->load('tags');

        $this->assertCount(2, $announcement->tags);
        $this->assertTrue(
            $announcement->tags->contains(static fn (Tag $tag): bool => $tag->category === 'exam' && $tag->name === '高考'),
        );
    }

    public function test_with_tags_scope_matches_all_specified_tags(): void
    {
        $fullTime = Tag::query()->create(['category' => 'rc', 'name' => '全职']);
        $central = Tag::query()->create(['category' => 'publisher', 'name' => '央企']);
        $campus = Tag::query()->create(['category' => 'rc', 'name' => '校招']);
        $highSalary = Tag::query()->create(['category' => 'rc', 'name' => '收入过万']);

        $matched = Announcement::query()->create([
            'title' => '全部标签公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $matched->tags()->sync([$fullTime->id, $central->id, $campus->id, $highSalary->id]);

        $partial = Announcement::query()->create([
            'title' => '部分标签公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $partial->tags()->sync([$fullTime->id, $central->id]);

        $ids = Announcement::query()
            ->withTags([
                ['category' => 'rc', 'name' => '全职'],
                ['category' => 'publisher', 'name' => '央企'],
                ['category' => 'rc', 'name' => '校招'],
                ['category' => 'rc', 'name' => '收入过万'],
            ])
            ->pluck('id')
            ->all();

        $this->assertSame([$matched->id], $ids);
    }

    public function test_with_tags_scope_can_match_any_tag(): void
    {
        $fullTime = Tag::query()->create(['category' => 'rc', 'name' => '全职']);
        $campus = Tag::query()->create(['category' => 'rc', 'name' => '校招']);

        $first = Announcement::query()->create([
            'title' => '全职公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $first->tags()->sync([$fullTime->id]);

        $second = Announcement::query()->create([
            'title' => '校招公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $second->tags()->sync([$campus->id]);

        $ids = Announcement::query()
            ->withTags([
                ['category' => 'rc', 'name' => '全职'],
                ['category' => 'rc', 'name' => '校招'],
            ], matchAll: false)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([$first->id, $second->id], $ids);
    }

    public function test_with_tags_scope_returns_empty_when_tag_not_found(): void
    {
        Announcement::query()->create([
            'title' => '无标签公告',
            'type' => CmsAnnouncementType::System,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $count = Announcement::query()
            ->withTags([['category' => 'rc', 'name' => '不存在的标签']])
            ->count();

        $this->assertSame(0, $count);
    }
}
