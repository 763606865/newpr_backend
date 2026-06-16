<?php

namespace Tests\Unit\Models\Cms;

use App\Models\Cms\FriendLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_friend_link_city_code_can_be_null_for_global_scope(): void
    {
        $friendLink = FriendLink::query()->create([
            'name' => '全站友链',
            'url' => 'https://example.com',
        ]);

        $this->assertNull($friendLink->city_code);
    }
}
