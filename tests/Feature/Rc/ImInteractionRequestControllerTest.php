<?php

namespace Tests\Feature\Rc;

use App\Enums\ImConversationType;
use App\Enums\ImInteractionRequestStatus;
use App\Enums\ImInteractionRequestType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Libs\Facades\Im;
use App\Models\ImConversation;
use App\Models\ImInteractionRequest;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Tests\TestCase;

class ImInteractionRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('RC IM Interaction Test', 'rc_users');
    }

    public function test_user_can_create_exchange_contact_interaction_request(): void
    {
        [$senderUser, $senderIdentity, $senderUserIm, , $receiverUserIm, $conversation] = $this->createInteractionContext();

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'interaction-request-message-1',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $response = $this->rcPostJson($senderUser, $senderIdentity, '/rc/im/interaction-requests', [
            'conversation_id' => $conversation->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => ImInteractionRequestType::ExchangeContact->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.interaction_request.type', ImInteractionRequestType::ExchangeContact->value)
            ->assertJsonPath('data.interaction_request.status', ImInteractionRequestStatus::Pending->value)
            ->assertJsonPath('data.message.id', 'interaction-request-message-1')
            ->assertJsonPath('data.card.type', ImInteractionRequestType::ExchangeContact->value)
            ->assertJsonPath('data.card.actions.0', 'accept')
            ->assertJsonPath('data.card.actions.1', 'reject');

        $this->assertDatabaseHas('im_interaction_requests', [
            'conversation_id' => $conversation->id,
            'sender_user_im_id' => $senderUserIm->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => ImInteractionRequestType::ExchangeContact->value,
            'status' => ImInteractionRequestStatus::Pending->value,
        ]);
        $this->assertNotNull($conversation->refresh()->last_message_at);
    }

    public function test_user_can_create_interview_response_interaction_request_with_application_id(): void
    {
        [$senderUser, $senderIdentity, , , $receiverUserIm, $conversation] = $this->createInteractionContext();

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'interaction-request-message-interview',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $response = $this->rcPostJson($senderUser, $senderIdentity, '/rc/im/interaction-requests', [
            'conversation_id' => $conversation->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => ImInteractionRequestType::RespondInterviewInvitation->value,
            'payload' => [
                'application_id' => 88,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.interaction_request.type', ImInteractionRequestType::RespondInterviewInvitation->value)
            ->assertJsonPath('data.card.payload.application_id', 88);
    }

    public function test_receiver_can_accept_exchange_contact_and_send_both_phone_numbers(): void
    {
        [$senderUser, $senderIdentity, $senderUserIm, $receiverIdentity, $receiverUserIm, $conversation] = $this->createInteractionContext();
        $interactionRequest = ImInteractionRequest::query()->create([
            'conversation_id' => $conversation->id,
            'sender_user_im_id' => $senderUserIm->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => ImInteractionRequestType::ExchangeContact,
            'status' => ImInteractionRequestStatus::Pending,
            'payload' => [],
        ]);

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'interaction-result-message-1',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $response = $this->rcPostJson($receiverIdentity->user, $receiverIdentity, '/rc/im/interaction-requests/'.$interactionRequest->id.'/respond', [
            'action' => 'accept',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.interaction_request.status', ImInteractionRequestStatus::Accepted->value)
            ->assertJsonPath('data.message.id', 'interaction-result-message-1')
            ->assertJsonPath('data.card.status', ImInteractionRequestStatus::Accepted->value)
            ->assertJsonPath('data.card.result.contacts.0.phone', $senderUser->phone)
            ->assertJsonPath('data.card.result.contacts.1.phone', $receiverIdentity->user->phone);

        $interactionRequest->refresh();

        $this->assertSame(ImInteractionRequestStatus::Accepted, $interactionRequest->status);
        $this->assertSame($senderUser->phone, $interactionRequest->result_payload['contacts'][0]['phone']);
        $this->assertSame($receiverIdentity->user->phone, $interactionRequest->result_payload['contacts'][1]['phone']);
        $this->assertNotNull($interactionRequest->responded_at);
    }

    public function test_sender_cannot_respond_to_interaction_request(): void
    {
        [$senderUser, $senderIdentity, $senderUserIm, , $receiverUserIm, $conversation] = $this->createInteractionContext();
        $interactionRequest = ImInteractionRequest::query()->create([
            'conversation_id' => $conversation->id,
            'sender_user_im_id' => $senderUserIm->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => ImInteractionRequestType::ExchangeContact,
            'status' => ImInteractionRequestStatus::Pending,
            'payload' => [],
        ]);

        Im::shouldReceive('conversation')->never();

        $this->rcPostJson($senderUser, $senderIdentity, '/rc/im/interaction-requests/'.$interactionRequest->id.'/respond', [
            'action' => 'accept',
        ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '只有接收方可以处理该请求。');

        $this->assertSame(ImInteractionRequestStatus::Pending, $interactionRequest->refresh()->status);
    }

    /**
     * @return array{0: User, 1: UserIdentity, 2: UserIm, 3: UserIdentity, 4: UserIm, 5: ImConversation}
     */
    private function createInteractionContext(): array
    {
        $receiver = User::factory()->create([
            'phone' => '13900000002',
        ]);
        $sender = User::factory()->create([
            'phone' => '13800000001',
        ]);

        $receiverIdentity = UserIdentity::withoutEvents(fn (): UserIdentity => UserIdentity::query()->create([
            'user_id' => $receiver->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]));
        $senderIdentity = UserIdentity::withoutEvents(fn (): UserIdentity => UserIdentity::query()->create([
            'user_id' => $sender->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]));
        $receiverUserIm = $this->createUserIm($receiver, $receiverIdentity, 'receiver');
        $senderUserIm = $this->createUserIm($sender, $senderIdentity, 'sender');
        $conversation = $this->createConversation($senderUserIm, $receiverUserIm);

        return [$sender, $senderIdentity, $senderUserIm, $receiverIdentity, $receiverUserIm, $conversation];
    }

    private function createUserIm(User $user, UserIdentity $identity, string $prefix): UserIm
    {
        return UserIm::query()->create([
            'user_id' => $user->id,
            'user_identity_id' => $identity->id,
            'identity_type' => $identity->identity_type,
            'provider' => 'custom',
            'app_code' => 'rc',
            'external_user_id' => $prefix.'-external-'.$identity->id,
            'im_user_id' => $prefix.'-im-'.$identity->id,
        ]);
    }

    private function createConversation(UserIm $senderUserIm, UserIm $receiverUserIm): ImConversation
    {
        $conversation = ImConversation::query()->create([
            'provider' => 'custom',
            'app_code' => 'rc',
            'conversation_no' => 'provider-conversation-interaction-'.$senderUserIm->id,
            'conversation_type' => ImConversationType::Single,
            'conversation_key' => 'single:rc_user_im:'.$senderUserIm->id.'|rc_user_im:'.$receiverUserIm->id,
            'owner_type' => 'rc_user_im',
            'owner_id' => $senderUserIm->id,
            'scene' => 'manual',
        ]);

        $conversation->members()->create([
            'member_type' => 'rc_user_im',
            'member_id' => $senderUserIm->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $conversation->members()->create([
            'member_type' => 'rc_user_im',
            'member_id' => $receiverUserIm->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rcPostJson(User $user, UserIdentity $identity, string $uri, array $data = [])
    {
        Auth::forgetGuards();

        return $this
            ->withHeader('Authorization', 'Bearer '.$this->rcBearerToken($user, $identity))
            ->postJson($uri, $data);
    }

    private function rcBearerToken(User $user, UserIdentity $identity): string
    {
        $tokenResult = app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc',
            [],
            'rc_users',
        );

        $token = $tokenResult->getToken();

        if ($token instanceof Token) {
            $token->responsible_type = UserIdentity::class;
            $token->responsible_id = $identity->id;
            $token->save();
        }

        return $tokenResult->accessToken;
    }
}
