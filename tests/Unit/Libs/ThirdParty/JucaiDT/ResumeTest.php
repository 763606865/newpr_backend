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

    public function test_detail(): void
    {
        $params = [
            'resume_id' => 121936
        ];

        $result = JucaiDT::resume()->detail($params);

        $this->assertEquals(1, $result['code']);
    }

    public function test_unlock()
    {
        $params = [
            'resume_id' => 121936
        ];

        $result = JucaiDT::resume()->unlock($params);

        $this->assertEquals(1, $result['code']);
    }

    public function test_attachments()
    {
        $params = [
            'resume_id' => 121936
        ];

        $result = JucaiDT::resume()->attachments($params);

        $this->assertEquals(1, $result['code']);
    }

    public function test_download()
    {
        $params = [
            'resume_id' => 121936,
            'attachment_id' => 1
        ];

        $result = JucaiDT::resume()->download($params);

        $this->assertEquals(1, $result['code']);
    }

    public function test_dict()
    {
        $dictionaries = [
            'sex',
            'education',
            'degree',
            'marital_status',
//            'cert',
//            'company',
//            'district',
//            'major',
            'nation',
            'politics',
//            'position',
            'salary',
            'experience',
            'nature',
        ];
        $params = [
            'type' => 'nature'
        ];

        $result = JucaiDT::resume()->dict($params);

        $this->assertEquals(1, $result['code']);
    }
}
