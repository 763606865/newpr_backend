<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\MajorLevel;
use App\Enums\MajorStatus;
use App\Enums\RcJobEmploymentType;
use App\Models\Area;
use App\Models\Cms\Tag;
use App\Models\Major;
use App\Models\Rc\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_includes_cities_majors_and_tags(): void
    {
        Area::query()->create([
            'name' => '苏州市',
            'code' => '320500',
            'parent_code' => '320000',
            'level' => 2,
            'type' => null,
        ]);

        Major::query()->create([
            'full_code' => '080901',
            'name' => '计算机科学与技术',
            'level' => MajorLevel::Major,
            'parent_code' => '0809',
            'type' => '高职专科',
            'sort' => 0,
            'status' => MajorStatus::Enabled,
        ]);

        $tag = Tag::query()->create([
            'category' => 'job',
            'name' => '中国企业500强',
            'slug' => 'china-top-500',
        ]);

        $announcement = Announcement::query()->create([
            'title' => '中粮集团2026届校园招聘',
            'publisher_name' => '中粮集团有限公司',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/cofco',
            'employment_types' => [RcJobEmploymentType::Campus->value, RcJobEmploymentType::Internship->value],
            'graduation_years' => [2026, 2027],
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addMonth(),
        ]);

        $announcement->tags()->sync([$tag->id]);
        $announcement->syncCityCodes(['320500']);
        $announcement->syncMajorCodes(['080901']);

        $searchable = $announcement->fresh(['tags', 'cities.cityArea', 'majors.major'])->toSearchableArray();

        $this->assertSame('rc_announcements', $announcement->searchableAs());
        $this->assertTrue($announcement->shouldBeSearchable());
        $this->assertTrue($announcement->isPubliclySearchable());
        $this->assertSame(['320500'], $searchable['city_codes']);
        $this->assertSame('苏州市', $searchable['city_names']);
        $this->assertSame(['080901'], $searchable['major_codes']);
        $this->assertSame('计算机科学与技术', $searchable['major_names']);
        $this->assertEqualsCanonicalizing([3, 4], $searchable['employment_types']);
        $this->assertSame([2026, 2027], $searchable['graduation_years']);
        $this->assertSame([$tag->id], $searchable['tag_ids']);
        $this->assertSame(1, $searchable['is_public']);
        $this->assertSame(1, $searchable['is_apply_open']);
    }

    public function test_draft_announcement_is_not_publicly_searchable(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '草稿公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/draft',
            'status' => CmsPublishStatus::Draft,
        ]);

        $searchable = $announcement->toSearchableArray();

        $this->assertFalse($announcement->isPubliclySearchable());
        $this->assertSame(0, $searchable['is_public']);
    }
}
