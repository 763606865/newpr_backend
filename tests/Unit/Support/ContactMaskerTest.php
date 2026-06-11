<?php

namespace Tests\Unit\Support;

use App\Support\ContactMasker;
use Tests\TestCase;

class ContactMaskerTest extends TestCase
{
    public function test_mask_phone_hides_middle_digits(): void
    {
        $this->assertSame('138****8000', ContactMasker::maskPhone('13800138000'));
    }

    public function test_mask_email_hides_local_part(): void
    {
        $this->assertSame('can******@example.com', ContactMasker::maskEmail('candidate@example.com'));
    }

    public function test_mask_resume_payload_masks_contact_fields(): void
    {
        $masked = ContactMasker::maskResumePayload([
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
        ]);

        $this->assertSame('138****8000', $masked['phone']);
        $this->assertSame('can******@example.com', $masked['email']);
        $this->assertSame('候选人甲', $masked['full_name']);
    }
}
