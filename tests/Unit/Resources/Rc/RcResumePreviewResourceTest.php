<?php

namespace Tests\Unit\Resources\Rc;

use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Resources\Rc\RcResumePreviewResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class RcResumePreviewResourceTest extends TestCase
{
    public function test_it_does_not_expose_private_contact_or_attachment_fields(): void
    {
        $resume = new Resume([
            'id' => 1,
            'user_id' => 10,
            'resume_no' => 'RC20260101000001',
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'id_card' => '360100199001011234',
            'file_url' => 'uploads/rc/resume/example.pdf',
            'file_name' => '简历.pdf',
            'file_ext' => 'pdf',
            'text_content' => '完整简历文本',
            'parsed_data' => ['school' => '浙江大学'],
            'current_residence_detail' => '红谷滩某小区',
            'household_register_detail' => '某街道',
            'status' => RcResumeStatus::Normal,
        ]);

        $payload = (new RcResumePreviewResource($resume))->resolve(new Request);

        $this->assertSame('候选人甲', $payload['full_name']);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('id_card', $payload);
        $this->assertArrayNotHasKey('file_url', $payload);
        $this->assertArrayNotHasKey('display_file_url', $payload);
        $this->assertArrayNotHasKey('file_name', $payload);
        $this->assertArrayNotHasKey('file_ext', $payload);
        $this->assertArrayNotHasKey('text_content', $payload);
        $this->assertArrayNotHasKey('parsed_data', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
        $this->assertArrayNotHasKey('current_residence_detail', $payload);
        $this->assertArrayNotHasKey('household_register_detail', $payload);
    }
}
