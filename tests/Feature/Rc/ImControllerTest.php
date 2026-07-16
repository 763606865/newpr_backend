<?php

namespace Tests\Feature\Rc;

use App\Enums\ImConversationType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Libs\Facades\Im;
use App\Models\ImConversation;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Tests\TestCase;

class ImControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('RC IM Test', 'rc_users');
    }

    public function test_user_can_create_single_conversation_with_member(): void
    {
        [$user, $identity, $ownerUserIm, $memberIdentity, $memberUserIm] = $this->createConversationContext();

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function store(array $payload): array
                {
                    return [
                        'id' => 'provider-conversation-1',
                        'payload' => $payload,
                    ];
                }
            });

        $response = $this->rcPostJson($user, $identity, '/rc/im/conversations', [
            'type' => ImConversationType::Single->value,
            'members' => [
                ['external_user_id' => $memberIdentity->external_user_id],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.conversation_no', 'provider-conversation-1')
            ->assertJsonPath('data.conversation_type', 'single')
            ->assertJsonPath('data.owner_type', 'rc_user_im')
            ->assertJsonPath('data.owner_id', $ownerUserIm->id)
            ->assertJsonCount(2, 'data.members');

        $this->assertDatabaseHas('im_conversations', [
            'conversation_no' => 'provider-conversation-1',
            'conversation_type' => ImConversationType::Single->value,
            'owner_type' => 'rc_user_im',
            'owner_id' => $ownerUserIm->id,
            'scene' => 'manual',
        ]);

        $this->assertDatabaseHas('im_conversation_members', [
            'member_type' => 'rc_user_im',
            'member_id' => $ownerUserIm->id,
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('im_conversation_members', [
            'member_type' => 'rc_user_im',
            'member_id' => $memberUserIm->id,
            'role' => 'member',
        ]);
    }

    public function test_repeated_store_returns_existing_conversation(): void
    {
        [$user, $identity, , $memberIdentity] = $this->createConversationContext();

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function store(array $payload): array
                {
                    return ['id' => 'provider-conversation-1'];
                }
            });

        $payload = [
            'type' => ImConversationType::Single->value,
            'members' => [
                ['external_user_id' => $memberIdentity->external_user_id],
            ],
        ];
        $firstResponse = $this->rcPostJson($user, $identity, '/rc/im/conversations', $payload);
        $secondResponse = $this->rcPostJson($user, $identity, '/rc/im/conversations', $payload);

        $firstResponse->assertOk()->assertJsonPath('code', 200);
        $secondResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $firstResponse->json('data.id'));

        $this->assertSame(1, ImConversation::query()->count());
    }

    public function test_single_conversation_requires_one_initialized_member(): void
    {
        [$user, $identity] = $this->createConversationContext();

        Im::shouldReceive('conversation')->never();

        $this->rcPostJson($user, $identity, '/rc/im/conversations', [
            'type' => ImConversationType::Single->value,
            'members' => [],
        ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '单聊会话只能初始化一名成员。');
    }

    /**
     * @return array{0: User, 1: UserIdentity, 2: UserIm, 3: UserIdentity, 4: UserIm}
     */
    private function createConversationContext(): array
    {
        $member = User::factory()->create();
        $user = User::factory()->create();

        $memberIdentity = UserIdentity::withoutEvents(fn (): UserIdentity => UserIdentity::query()->create([
            'user_id' => $member->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]));
        $identity = UserIdentity::withoutEvents(fn (): UserIdentity => UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]));
        $memberUserIm = $this->createUserIm($member, $memberIdentity, 'member');
        $userIm = $this->createUserIm($user, $identity, 'owner');

        return [$user, $identity, $userIm, $memberIdentity, $memberUserIm];
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
