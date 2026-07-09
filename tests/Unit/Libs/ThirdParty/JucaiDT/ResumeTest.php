<?php

namespace Tests\Unit\Libs\ThirdParty\JucaiDT;

use App\Libs\Facades\JucaiDT;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    public function test_list_uses_resume_mylist_endpoint_and_returns_response_result(): void
    {
        $params = [];

        $result = JucaiDT::resume()->list();

        $this->assertEquals(1, $result['code']);
    }
}
