<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use App\Models\AdminUser;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_inquiry_casts_enums_and_dates(): void
    {
        $inquiry = ContactInquiry::factory()->create([
            'product' => RcContactProduct::CampusRecruitment,
            'source' => 'mini_program',
        ]);

        $this->assertSame('contact_inquiries', $inquiry->getTable());
        $this->assertSame('mini_program', $inquiry->source);
        $this->assertSame(RcContactProduct::CampusRecruitment, $inquiry->product);
        $this->assertSame(RcContactInquiryStatus::Pending, $inquiry->status);
        $this->assertNotNull($inquiry->submitted_at);
        $this->assertNull($inquiry->followed_up_at);
    }

    public function test_followed_up_inquiry_belongs_to_admin_user(): void
    {
        $admin = AdminUser::query()->create([
            'name' => '运营人员',
            'email' => 'operator@example.com',
            'password' => 'password',
        ]);
        $inquiry = ContactInquiry::factory()->followedUp($admin)->create();

        $this->assertSame(RcContactInquiryStatus::FollowedUp, $inquiry->status);
        $this->assertTrue($inquiry->followUpAdmin->is($admin));
        $this->assertNotNull($inquiry->followed_up_at);
    }
}
