<?php

namespace Tests\Feature\Filament;

use App\Enums\RcContactInquiryStatus;
use App\Filament\Resources\ContactInquiries\Pages\ListContactInquiries;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class ContactInquiryResourceTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_admin_can_view_contact_inquiries(): void
    {
        $this->actingAsFilamentAdmin($this->contactInquiryPermissions());
        $inquiries = ContactInquiry::factory()->count(3)->create();

        Livewire::test(ListContactInquiries::class)
            ->assertCanSeeTableRecords($inquiries);
    }

    public function test_admin_can_mark_pending_inquiry_as_followed_up(): void
    {
        $admin = $this->actingAsFilamentAdmin($this->contactInquiryPermissions());
        $inquiry = ContactInquiry::factory()->create();

        Livewire::test(ListContactInquiries::class)
            ->assertTableActionVisible('followUp', $inquiry)
            ->callTableAction('followUp', $inquiry, data: [
                'follow_up_note' => '已电话联系，客户希望下周提供方案。',
            ])
            ->assertNotified();

        $inquiry->refresh();

        $this->assertSame(RcContactInquiryStatus::FollowedUp, $inquiry->status);
        $this->assertSame($admin->id, $inquiry->follow_up_admin_user_id);
        $this->assertSame('已电话联系，客户希望下周提供方案。', $inquiry->follow_up_note);
        $this->assertNotNull($inquiry->followed_up_at);
    }

    public function test_follow_up_action_is_hidden_after_inquiry_was_followed_up(): void
    {
        $admin = $this->actingAsFilamentAdmin($this->contactInquiryPermissions());
        $inquiry = ContactInquiry::factory()->followedUp($admin)->create();

        Livewire::test(ListContactInquiries::class)
            ->assertTableActionHidden('followUp', $inquiry);
    }

    /**
     * @return array<int, string>
     */
    private function contactInquiryPermissions(): array
    {
        return [
            'ViewAny:ContactInquiry',
            'View:ContactInquiry',
            'Update:ContactInquiry',
            'Delete:ContactInquiry',
        ];
    }
}
