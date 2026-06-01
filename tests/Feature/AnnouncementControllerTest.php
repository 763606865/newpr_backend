<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnnouncementControllerTest extends TestCase
{
    public function test_index_returns_announcement_page_payload(): void
    {
        if (! $this->hasCmsAnnouncementTables()) {
            $this->markTestSkipped('CMS announcement tables are not available in current test database.');
        }

        $response = $this->getJson('/cms/announcements');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'code',
                'data' => [
                    'banner_position',
                    'ad_slot',
                    'announcements',
                ],
                'meta' => [
                    'timestamp',
                    'response_time',
                ],
            ]);
    }

    public function test_show_returns_not_found_when_record_is_missing(): void
    {
        if (! $this->hasCmsAnnouncementTables()) {
            $this->markTestSkipped('CMS announcement tables are not available in current test database.');
        }

        $response = $this->getJson('/cms/announcements/999999');

        $response->assertNotFound();
    }

    private function hasCmsAnnouncementTables(): bool
    {
        return Schema::hasTable('cms_banner_positions')
            && Schema::hasTable('cms_ad_slots')
            && Schema::hasTable('cms_announcements');
    }
}
