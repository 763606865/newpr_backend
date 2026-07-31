<?php

namespace Tests\Feature\Cms;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_guest_can_submit_contact_inquiry(): void
    {
        $response = $this
            ->withHeader('User-Agent', 'Contact Inquiry Test')
            ->postJson('/cms/contact-inquiries', [
                'name' => '张先生',
                'phone' => '13800138000',
                'company_name' => '示例科技有限公司',
                'source' => 'website',
                'product' => RcContactProduct::RecruitmentService->value,
                'content' => '希望了解企业招聘服务和相关套餐。',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.status', RcContactInquiryStatus::Pending->value)
            ->assertJsonStructure(['data' => ['id', 'status', 'submitted_at']]);

        $this->assertDatabaseHas('contact_inquiries', [
            'name' => '张先生',
            'phone' => '13800138000',
            'company_name' => '示例科技有限公司',
            'source' => 'website',
            'product' => RcContactProduct::RecruitmentService->value,
            'status' => RcContactInquiryStatus::Pending->value,
            'user_agent' => 'Contact Inquiry Test',
        ]);
    }

    public function test_contact_inquiry_requires_valid_contact_details(): void
    {
        $this->postJson('/cms/contact-inquiries', [
            'name' => '张',
            'phone' => '123',
            'product' => 255,
            'content' => '太短',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'product', 'content']);
    }
}
