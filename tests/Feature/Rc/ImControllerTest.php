<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\ImBusinessCardType;
use App\Enums\ImConversationType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Libs\Facades\Im;
use App\Models\Company;
use App\Models\ImConversation;
use App\Models\ImSystemUser;
use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeFavorite;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;
use App\Models\Token;
use App\Models\User;
use App\Services\IMService;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Tests\TestCase;

class ImControllerTest extends TestCase
{
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
        $imConversationApi = new class
        {
            /**
             * @var list<array<string, mixed>>
             */
            public array $messages = [];

            public function store(array $payload): array
            {
                return [
                    'id' => 'provider-conversation-1',
                    'payload' => $payload,
                ];
            }

            public function postMessage(int|string $conversationId, array $params): array
            {
                $this->messages[] = [
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];

                return [
                    'id' => 'initial-message-1',
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];
            }
        };

        Im::shouldReceive('conversation')
            ->twice()
            ->andReturn($imConversationApi);

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

        $this->assertNotNull(ImConversation::query()->firstOrFail()->last_message_at);
        $this->assertSame([
            [
                'conversation_id' => 'provider-conversation-1',
                'payload' => [
                    'sender_user_id' => $ownerUserIm->external_user_id,
                    'message_type' => 'text',
                    'content' => [
                        'card_type' => 'text',
                        'text' => 'Hi，看了您的过往经历感觉您比较符合我们的职位要求，方便聊一聊吗?',
                    ],
                    'metadata' => [
                        'source' => 'conversation_initial_message',
                        'sender_user_im_id' => $ownerUserIm->id,
                        'sender_user_identity_id' => $identity->id,
                    ],
                ],
            ],
        ], $imConversationApi->messages);

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
            ->twice()
            ->andReturn(new class
            {
                public function store(array $payload): array
                {
                    return ['id' => 'provider-conversation-1'];
                }

                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'initial-message-1',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
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

    public function test_job_seeker_initial_message_is_sent_after_creating_conversation(): void
    {
        [, , $recruiterUserIm, $jobSeekerIdentity, $jobSeekerUserIm] = $this->createConversationContext();
        $imConversationApi = new class
        {
            /**
             * @var list<array<string, mixed>>
             */
            public array $messages = [];

            public function store(array $payload): array
            {
                return [
                    'id' => 'provider-conversation-job-seeker',
                    'payload' => $payload,
                ];
            }

            public function postMessage(int|string $conversationId, array $params): array
            {
                $this->messages[] = [
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];

                return [
                    'id' => 'initial-message-job-seeker',
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];
            }
        };

        Im::shouldReceive('conversation')
            ->twice()
            ->andReturn($imConversationApi);

        $this->rcPostJson($jobSeekerIdentity->user, $jobSeekerIdentity, '/rc/im/conversations', [
            'type' => ImConversationType::Single->value,
            'members' => [
                ['external_user_id' => $recruiterUserIm->external_user_id],
            ],
        ])->assertOk()->assertJsonPath('code', 200);

        $this->assertSame('provider-conversation-job-seeker', $imConversationApi->messages[0]['conversation_id']);
        $this->assertSame($jobSeekerUserIm->external_user_id, $imConversationApi->messages[0]['payload']['sender_user_id']);
        $this->assertSame('希望和你聊聊这个职位，是否有时间呢？', $imConversationApi->messages[0]['payload']['content']['text']);
    }

    public function test_system_notice_can_be_sent_to_specific_user_im(): void
    {
        [, , $receiver] = $this->createConversationContext();
        $systemUser = ImSystemUser::query()->create([
            'code' => 'rc_notice',
            'name' => '系统通知',
            'provider' => 'custom',
            'app_code' => 'rc',
            'external_user_id' => 'system',
            'is_active' => true,
        ]);
        $imConversationApi = new class
        {
            /**
             * @var list<array<string, mixed>>
             */
            public array $conversations = [];

            /**
             * @var list<array<string, mixed>>
             */
            public array $messages = [];

            public function store(array $payload): array
            {
                $this->conversations[] = $payload;

                return [
                    'id' => 'provider-system-conversation-1',
                    'payload' => $payload,
                ];
            }

            public function postMessage(int|string $conversationId, array $params): array
            {
                $this->messages[] = [
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];

                return [
                    'id' => 'system-message-1',
                    'conversation_id' => $conversationId,
                    'payload' => $params,
                ];
            }
        };

        Im::shouldReceive('conversation')
            ->twice()
            ->andReturn($imConversationApi);

        $result = IMService::make()->sendSystemNotice($receiver->external_user_id, [
            'notice_type' => 'interview_invited',
            'title' => '面试提醒',
            'summary' => '您有一场新的面试邀请。',
            'biz_id' => 'interview_10',
            'action_url' => '/rc/interviews/10',
            'client_msg_id' => 'interview_invited_10',
        ], $systemUser);

        $this->assertSame('system-message-1', $result['message']['id']);
        $this->assertSame([
            'conversation_key' => 'system:rc_user_im:'.$receiver->id,
            'type' => 'single',
            'scene' => 'system',
            'subject' => '系统通知',
            'owner_user_id' => 'system',
            'member_user_ids' => ['rc_user_im:'.$receiver->id],
            'metadata' => [
                'channel' => 'system_notice',
                'system' => true,
                'system_user_id' => $systemUser->id,
                'receiver_user_im_id' => $receiver->id,
            ],
        ], $imConversationApi->conversations[0]);
        $this->assertSame('provider-system-conversation-1', $imConversationApi->messages[0]['conversation_id']);
        $this->assertSame('system', $imConversationApi->messages[0]['payload']['sender_user_id']);
        $this->assertSame('system_notice', $imConversationApi->messages[0]['payload']['message_type']);
        $this->assertSame('interview_invited_10', $imConversationApi->messages[0]['payload']['client_msg_id']);
        $this->assertSame('interview_invited', $imConversationApi->messages[0]['payload']['content']['notice_type']);
        $this->assertSame('面试提醒', $imConversationApi->messages[0]['payload']['content']['title']);
        $this->assertSame('您有一场新的面试邀请。', $imConversationApi->messages[0]['payload']['content']['summary']);
        $this->assertSame('interview_10', $imConversationApi->messages[0]['payload']['content']['biz_id']);
        $this->assertSame('/rc/interviews/10', $imConversationApi->messages[0]['payload']['content']['action_url']);

        $this->assertDatabaseHas('im_conversations', [
            'conversation_no' => 'provider-system-conversation-1',
            'conversation_type' => ImConversationType::Single->value,
            'conversation_key' => 'system:rc_user_im:'.$receiver->id,
            'owner_type' => 'im_system_user',
            'owner_id' => $systemUser->id,
            'scene' => 'system',
        ]);
        $this->assertDatabaseHas('im_conversation_members', [
            'member_type' => 'im_system_user',
            'member_id' => $systemUser->id,
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('im_conversation_members', [
            'member_type' => 'rc_user_im',
            'member_id' => $receiver->id,
            'role' => 'member',
        ]);
    }

    public function test_job_id_is_saved_as_conversation_context(): void
    {
        [$user, $identity, , $memberIdentity] = $this->createConversationContext();
        $job = $this->createJob('JOB-IM-CONTEXT-001');
        $identity->update([
            'organization_type' => 'company',
            'organization_id' => $job->company_id,
            'organization_name' => 'IM 会话测试企业',
        ]);
        JobFavorite::query()->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);
        $resume = Resume::query()->create([
            'user_id' => $memberIdentity->user_id,
            'title' => '沟通简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
        ]);
        ResumeFavorite::query()->create([
            'user_id' => $user->id,
            'company_id' => $job->company_id,
            'resume_id' => $resume->id,
        ]);

        Im::shouldReceive('conversation')
            ->twice()
            ->andReturn(new class
            {
                public function store(array $payload): array
                {
                    return ['id' => 'provider-conversation-with-job'];
                }

                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'initial-message-1',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $response = $this->rcPostJson($user, $identity, '/rc/im/conversations', [
            'type' => ImConversationType::Single->value,
            'job_id' => $job->id,
            'members' => [
                ['external_user_id' => $memberIdentity->external_user_id],
            ],
            'metadata' => [
                'resume_id' => $resume->id,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.context_type', 'job')
            ->assertJsonPath('data.context_id', $job->id)
            ->assertJsonPath('data.context.type', 'job')
            ->assertJsonPath('data.context.id', $job->id)
            ->assertJsonPath('data.context.title', 'IM 沟通职位')
            ->assertJsonPath('data.context.salary_min', '8000.00')
            ->assertJsonPath('data.context.salary_max', '15000.00')
            ->assertJsonPath('data.context.salary_unit', 1)
            ->assertJsonPath('data.context.salary_unit_label', '月')
            ->assertJsonPath('data.context.annual_salary_months', '13.0')
            ->assertJsonMissingPath('data.context.is_favorited')
            ->assertJsonPath('data.viewer_context.viewer_identity_type', RcIdentityType::Recruiter->value)
            ->assertJsonPath('data.viewer_context.is_job_favorited', null)
            ->assertJsonPath('data.viewer_context.is_resume_favorited', true)
            ->assertJsonPath('data.context.benefit', '五险一金');

        $this->assertDatabaseHas('im_conversations', [
            'conversation_no' => 'provider-conversation-with-job',
            'context_type' => 'job',
            'context_id' => $job->id,
        ]);

        $conversation = ImConversation::query()->firstOrFail();

        $this->assertStringContainsString(':context:job:'.$job->id, (string) $conversation->conversation_key);
        $this->assertTrue($conversation->context?->is($job));
    }

    public function test_same_members_can_create_different_conversations_for_different_jobs(): void
    {
        [$user, $identity, , $memberIdentity] = $this->createConversationContext();
        $firstJob = $this->createJob('JOB-IM-CONTEXT-002');
        $secondJob = $this->createJob('JOB-IM-CONTEXT-003');

        Im::shouldReceive('conversation')
            ->times(4)
            ->andReturn(new class
            {
                private int $times = 0;

                public function store(array $payload): array
                {
                    $this->times++;

                    return ['id' => 'provider-conversation-job-'.$this->times];
                }

                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'initial-message-'.$this->times,
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $basePayload = [
            'type' => ImConversationType::Single->value,
            'members' => [
                ['external_user_id' => $memberIdentity->external_user_id],
            ],
        ];

        $this->rcPostJson($user, $identity, '/rc/im/conversations', [
            ...$basePayload,
            'job_id' => $firstJob->id,
        ])->assertOk()->assertJsonPath('code', 200);

        $this->rcPostJson($user, $identity, '/rc/im/conversations', [
            ...$basePayload,
            'job_id' => $secondJob->id,
        ])->assertOk()->assertJsonPath('code', 200);

        $this->assertSame(2, ImConversation::query()->count());
        $this->assertDatabaseHas('im_conversations', [
            'context_type' => 'job',
            'context_id' => $firstJob->id,
        ]);
        $this->assertDatabaseHas('im_conversations', [
            'context_type' => 'job',
            'context_id' => $secondJob->id,
        ]);
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

    public function test_recruiter_can_send_business_card_message(): void
    {
        [$user, $identity, $ownerUserIm, , $memberUserIm] = $this->createConversationContext();
        $conversation = $this->createConversation($ownerUserIm, $memberUserIm);

        Im::shouldReceive('conversation')
            ->once()
            ->andReturn(new class
            {
                public function postMessage(int|string $conversationId, array $params): array
                {
                    return [
                        'id' => 'message-1',
                        'conversation_id' => $conversationId,
                        'payload' => $params,
                    ];
                }
            });

        $response = $this->rcPostJson($user, $identity, '/rc/im/conversations/'.$conversation->id.'/card-messages', [
            'card_type' => ImBusinessCardType::RecruiterInviteInterview->value,
            'summary' => '邀请你参加面试',
            'biz' => [
                'application_id' => 10,
                'interview_id' => 20,
            ],
            'snapshot' => [
                'job_title' => '后端工程师',
                'interview_at' => '2026-07-22 10:00:00',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.message.id', 'message-1')
            ->assertJsonPath('data.card.card_type', ImBusinessCardType::RecruiterInviteInterview->value)
            ->assertJsonPath('data.card.title', '邀请面试')
            ->assertJsonPath('data.card.biz.application_id', 10)
            ->assertJsonPath('data.card.snapshot.job_title', '后端工程师');

        $this->assertNotNull($conversation->refresh()->last_message_at);
    }

    public function test_job_seeker_cannot_send_recruiter_card_message(): void
    {
        [$recruiter, $recruiterIdentity, $ownerUserIm, $jobSeekerIdentity, $memberUserIm] = $this->createConversationContext();
        $conversation = $this->createConversation($ownerUserIm, $memberUserIm);
        $jobSeeker = $jobSeekerIdentity->user;

        Im::shouldReceive('conversation')->never();

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/im/conversations/'.$conversation->id.'/card-messages', [
            'card_type' => ImBusinessCardType::RecruiterInviteInterview->value,
        ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '当前身份不可发送该卡片。');

        $this->assertTrue($recruiter->is($recruiterIdentity->user));
    }

    public function test_send_system_notice()
    {
        $identity = UserIdentity::first();
        $payload = [
            'notice_type' => 'coupon',
            'title' => '您有一条新消息',
            'summary' => '恭喜您活动一张优惠券!🎉🎉🎉',
            'biz_id' => '',
            'action_url' => '',
        ];
        $systemUser = ImSystemUser::first();

        IMService::make()->sendSystemNotice($identity->external_user_id, $payload, $systemUser);
        $this->assertEquals(200, 200);
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

    private function createConversation(UserIm $ownerUserIm, UserIm $memberUserIm): ImConversation
    {
        $conversation = ImConversation::query()->create([
            'provider' => 'custom',
            'app_code' => 'rc',
            'conversation_no' => 'provider-conversation-card',
            'conversation_type' => ImConversationType::Single,
            'conversation_key' => 'single:rc_user_im:'.$ownerUserIm->id.'|rc_user_im:'.$memberUserIm->id,
            'owner_type' => 'rc_user_im',
            'owner_id' => $ownerUserIm->id,
            'scene' => 'manual',
        ]);

        $conversation->members()->create([
            'member_type' => 'rc_user_im',
            'member_id' => $ownerUserIm->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $conversation->members()->create([
            'member_type' => 'rc_user_im',
            'member_id' => $memberUserIm->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return $conversation;
    }

    private function createJob(string $code): Job
    {
        $company = Company::query()->create([
            'name' => 'IM 会话测试企业',
            'credit_code' => '91360100'.substr(md5($code), 0, 10),
            'status' => CompanyStatus::Enabled,
        ]);

        return Job::query()->create([
            'company_id' => $company->id,
            'code' => $code,
            'title' => 'IM 沟通职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'salary_min' => 8000,
            'salary_max' => 15000,
            'annual_salary_months' => 13,
            'description' => '用于会话上下文测试',
            'benefit' => '五险一金',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
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
