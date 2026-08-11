<?php

namespace Tests\Unit\Libs\ThirdParty\CJWL;

use App\Libs\Facades\CJWL;
use Tests\TestCase;

class RecruitmentDetailTest extends TestCase
{
    public function test_cjwl_recruitmentDetail(): void
    {
        $result = CJWL::recruitmentDetail()->query();

        $this->assertEquals(1, $result['code']);
    }

    public function test_cjwl_recruitmentDetail_empty(): void
    {
        $result = CJWL::recruitmentDetail()->detail(['detail_id' => 1]);

        $this->assertEquals(1, $result['code']);
    }
}
