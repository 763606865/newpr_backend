<?php

namespace Tests\Feature\Cms;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Announcement;
use App\Models\Cms\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementListControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_filters_by_tag_ids_with_all_match(): void
    {
        $fullTime = Tag::query()->create(['category' => 'rc', 'name' => '全职']);
        $campus = Tag::query()->create(['category' => 'rc', 'name' => '校招']);

        $matched = Announcement::query()->create([
            'title' => '匹配公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $matched->tags()->sync([$fullTime->id, $campus->id]);

        $partial = Announcement::query()->create([
            'title' => '部分匹配公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $partial->tags()->sync([$fullTime->id]);

        $response = $this->getJson('/cms/announcements?'.http_build_query([
            'tag_ids' => [$fullTime->id, $campus->id],
            'per_page' => 15,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $matched->id);
    }

    public function test_index_filters_by_tag_ids_with_any_match(): void
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

        $response = $this->getJson('/cms/announcements?'.http_build_query([
            'tag_ids' => [$fullTime->id, $campus->id],
            'tags_match' => 'any',
            'per_page' => 15,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $ids);
    }

    public function test_index_accepts_comma_separated_tag_ids(): void
    {
        $tag = Tag::query()->create(['category' => 'rc', 'name' => '校招']);

        $announcement = Announcement::query()->create([
            'title' => '校招公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $announcement->tags()->sync([$tag->id]);

        $response = $this->getJson('/cms/announcements?tag_ids='.$tag->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $announcement->id);
    }

    public function test_index_rejects_invalid_tag_ids(): void
    {
        $response = $this->getJson('/cms/announcements?tag_ids[]=999999');

        $response->assertStatus(422);
    }

    public function test_index_filters_by_publisher_types(): void
    {
        $bank = Announcement::query()->create([
            'title' => '银行招聘公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'publisher_type' => CmsAnnouncementPublisherType::Bank,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '学校招聘公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'publisher_type' => CmsAnnouncementPublisherType::School,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/cms/announcements?'.http_build_query([
            'publisher_types' => [CmsAnnouncementPublisherType::Bank->value],
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $bank->id);
    }

    public function test_index_accepts_comma_separated_publisher_types(): void
    {
        $government = Announcement::query()->create([
            'title' => '政府招聘公告',
            'type' => CmsAnnouncementType::ExamRecruitment,
            'publisher_type' => CmsAnnouncementPublisherType::Government,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $bank = Announcement::query()->create([
            'title' => '银行招聘公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'publisher_type' => CmsAnnouncementPublisherType::Bank,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/cms/announcements?publisher_types=4,5');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$government->id, $bank->id], $ids);
    }

    public function test_index_rejects_invalid_publisher_types(): void
    {
        $response = $this->getJson('/cms/announcements?publisher_types[]=100');

        $response->assertStatus(422);
    }

    public function test_index_filters_by_region_code_with_district_priority(): void
    {
        $matched = Announcement::query()->create([
            'title' => '南昌红谷滩公告',
            'type' => CmsAnnouncementType::LocalPolicy,
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360113',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '南昌其他区公告',
            'type' => CmsAnnouncementType::LocalPolicy,
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360102',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '全站公告',
            'type' => CmsAnnouncementType::System,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/cms/announcements?'.http_build_query([
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360113',
            'per_page' => 15,
        ]));

        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->assertContains($matched->id, $ids);
    }
}
