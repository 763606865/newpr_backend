<?php

namespace Tests\Unit\Models;

use App\Enums\ImConversationType;
use App\Enums\RcIdentityType;
use App\Models\ImConversation;
use App\Models\ImConversationMember;
use App\Models\Rc\UserIm;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImConversationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_im_conversation_owner_resolves_to_rc_user_im(): void
    {
        $userIm = $this->createUserIm(1);
        $otherUserIm = $this->createUserIm(2);

        $conversation = ImConversation::query()->create([
            'provider' => 'custom',
            'app_code' => 'rc',
            'conversation_no' => 'conversation-1',
            'conversation_type' => ImConversationType::Single,
            'conversation_key' => 'single:rc_user_im:'.$userIm->id.'|rc_user_im:'.$otherUserIm->id,
            'owner_type' => 'rc_user_im',
            'owner_id' => $userIm->id,
            'scene' => 'job_chat',
            'metadata' => ['job_id' => 1],
        ]);

        $member = ImConversationMember::query()->create([
            'conversation_id' => $conversation->id,
            'member_type' => 'rc_user_im',
            'member_id' => $userIm->id,
            'role' => 'owner',
            'settings' => ['muted' => false],
        ]);

        ImConversationMember::query()->create([
            'conversation_id' => $conversation->id,
            'member_type' => 'rc_user_im',
            'member_id' => $otherUserIm->id,
            'role' => 'member',
        ]);

        $this->assertTrue($conversation->owner->is($userIm));
        $this->assertTrue($userIm->conversations->first()?->is($conversation));
        $this->assertCount(2, $conversation->members);
        $this->assertCount(2, $conversation->userImMembers);
        $this->assertTrue($member->conversation->is($conversation));
        $this->assertTrue($member->member->is($userIm));
        $this->assertTrue($userIm->conversationMembers->first()?->is($member));
        $this->assertTrue($userIm->memberConversations->first()?->is($conversation));
        $this->assertSame('single:rc_user_im:'.$userIm->id.'|rc_user_im:'.$otherUserIm->id, $conversation->conversation_key);
        $this->assertSame(ImConversationType::Single, $conversation->conversation_type);
        $this->assertSame(['job_id' => 1], $conversation->metadata);
        $this->assertSame(['muted' => false], $member->settings);
    }

    public function test_rc_user_im_morph_alias_is_registered(): void
    {
        $this->assertSame(UserIm::class, Relation::getMorphedModel('rc_user_im'));
    }

    private function createUserIm(int $identityId): UserIm
    {
        $user = User::factory()->create();

        return UserIm::query()->create([
            'user_id' => $user->id,
            'user_identity_id' => $identityId,
            'identity_type' => RcIdentityType::JobSeeker,
            'provider' => 'custom',
            'app_code' => 'rc',
            'external_user_id' => 'identity-'.$identityId,
            'im_user_id' => 'im-user-'.$identityId,
        ]);
    }
}
