<?php

namespace Tests\Feature\SApi;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Announcement;
use App\Models\SApi\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithSApiSignatures;
use Tests\TestCase;

class AnnouncementControllerTest extends TestCase
{
    use InteractsWithSApiSignatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_signature(): void
    {
        $this->getJson('/sapi/announcements')
            ->assertUnauthorized();
    }

    public function test_index_returns_published_announcements(): void
    {
        $client = Client::factory()->create();

        $included = $this->createAnnouncement([
            'title' => '已发布公告',
            'status' => CmsPublishStatus::Published,
            'created_at' => '2026-06-02 10:00:00',
        ]);
        $this->createAnnouncement([
            'title' => '草稿公告',
            'status' => CmsPublishStatus::Draft,
            'created_at' => '2026-06-02 11:00:00',
        ]);

        $response = $this->signedGet($client, '/sapi/announcements');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonPath('data.data.0.title', '已发布公告')
            ->assertJsonPath('data.data.0.status', CmsPublishStatus::Published->value)
            ->assertJsonPath('data.data.0.created_at', '2026-06-02 10:00:00');
    }

    public function test_index_filters_by_created_at_range(): void
    {
        $client = Client::factory()->create();

        $inRange = $this->createAnnouncement([
            'title' => '范围内',
            'status' => CmsPublishStatus::Published,
            'created_at' => '2026-06-02 12:00:00',
        ]);
        $this->createAnnouncement([
            'title' => '范围外',
            'status' => CmsPublishStatus::Published,
            'created_at' => '2026-06-05 12:00:00',
        ]);

        $query = [
            'created_from' => '2026-06-01 00:00:00',
            'created_to' => '2026-06-03 23:59:59',
        ];

        $response = $this->signedGet($client, '/sapi/announcements', $query);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $inRange->id);
    }

    public function test_index_filters_by_city_code(): void
    {
        $client = Client::factory()->create();

        $cityAnnouncement = $this->createAnnouncement([
            'title' => '南昌公告',
            'city_code' => '360100',
            'status' => CmsPublishStatus::Published,
        ]);
        $this->createAnnouncement([
            'title' => '全站公告',
            'city_code' => null,
            'status' => CmsPublishStatus::Published,
        ]);
        $this->createAnnouncement([
            'title' => '其他城市',
            'city_code' => '110100',
            'status' => CmsPublishStatus::Published,
        ]);

        $response = $this->signedGet($client, '/sapi/announcements', [
            'city_code' => '360100',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($cityAnnouncement->id, $ids);
    }

    public function test_index_rejects_invalid_created_at_format(): void
    {
        $client = Client::factory()->create();

        $this->signedGet($client, '/sapi/announcements', [
            'created_from' => '2026-06-02',
        ])->assertStatus(422);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function signedGet(Client $client, string $path, array $query = []): TestResponse
    {
        $uri = $query === [] ? $path : $path.'?'.http_build_query($query);

        return $this->withHeaders(
            $this->sapiSignatureHeaders($client, 'GET', $uri, $query),
        )->get($uri);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAnnouncement(array $overrides = []): Announcement
    {
        $createdAt = isset($overrides['created_at'])
            ? Carbon::parse($overrides['created_at'])
            : Carbon::parse('2026-06-02 09:00:00');
        unset($overrides['created_at']);

        $announcement = Announcement::query()->create(array_merge([
            'title' => '测试公告',
            'type' => CmsAnnouncementType::SelfPublished,
            'status' => CmsPublishStatus::Published,
            'published_at' => Carbon::parse('2026-06-02 09:00:00'),
        ], $overrides));

        $announcement->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $announcement->fresh();
    }
}
