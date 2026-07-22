<?php

namespace App\Services;

use App\Enums\ImConversationType;
use App\Enums\RcIdentityType;
use App\Exceptions\BadRequestException;
use App\Libs\Facades\Im;
use App\Models\ImConversation;
use App\Models\ImConversationMember;
use App\Models\ImSystemUser;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Sqids\Sqids;

class IMService extends Service
{
    public function createOrUpdate(UserIdentity $identity): UserIm
    {
        $user = $identity->user;
        $params = [
            'external_user_id' => $identity->external_user_id,
            'nickname' => $user->nickname,
            'avatar_url' => $user->display_avatar,
            'user_type' => 'normal',
        ];
        $im = Im::user();
        $imDriver = $im->getDriver();
        $response = $im->createOrUpdateUser($params);

        return UserIm::query()->updateOrCreate([
            'user_id' => $user->id,
            'user_identity_id' => $identity->id,
            'identity_type' => $identity->identity_type,
            'provider' => $imDriver->getProvider(),
            'app_code' => $imDriver->getAppCode(),
        ], [
            'external_user_id' => $identity->external_user_id,
            'im_user_id' => $response['id'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BadRequestException
     */
    public function resolvedToken(UserIdentity $identity): array
    {
        $userIm = UserIm::where('user_identity_id', $identity->id)->first();
        if (! $userIm) {
            $this->createOrUpdate($identity);
        }
        $data = Im::user()->getImToken($identity->external_user_id);

        return $data ?? [];
    }

    /**
     * @param array{
     *     type: string|ImConversationType,
     *     subject?: string|null,
     *     job_id?: int|null,
     *     members?: list<array{external_user_id: string}>,
     *     metadata?: array<string, mixed>
     * } $payload
     *
     * @throws \Throwable
     */
    public function resolvedConversation(UserIdentity $identity, array $payload = []): ImConversation
    {
        $conversationType = $this->resolveConversationType($payload['type'] ?? null);
        $members = $payload['members'] ?? [];

        $this->validateInitialMembers($conversationType, $members);

        $memberIdentities = [$identity->id => $identity];

        foreach ($members as $member) {
            $memberIdentity = $this->resolveIdentityFromExternalUserId((string) $member['external_user_id']);
            $memberIdentities[$memberIdentity->id] = $memberIdentity;
        }

        $userIms = collect($memberIdentities)
            ->map(fn (UserIdentity $memberIdentity): UserIm => $this->resolveUserIm($memberIdentity))
            ->values();

        $ownerUserIm = $userIms
            ->first(fn (UserIm $userIm): bool => $userIm->user_identity_id === $identity->id);

        if (! $ownerUserIm instanceof UserIm) {
            throw new InvalidArgumentException('当前用户 IM 账号不存在。');
        }

        $context = $this->resolveConversationContext($payload);
        $conversationKey = $this->conversationKey($conversationType, $userIms->all(), $context);
        $conversationMetadata = $this->conversationMetadata($payload);

        $existingConversation = ImConversation::query()
            ->where('conversation_key', $conversationKey)
            ->with(['members.member'])
            ->first();

        if ($existingConversation instanceof ImConversation) {
            foreach ($userIms as $userIm) {
                $this->syncConversationMember(
                    $existingConversation,
                    $userIm,
                    $userIm->id === $ownerUserIm->id ? 'owner' : 'member',
                );
            }

            if ($conversationMetadata !== []) {
                $existingConversation->forceFill([
                    'metadata' => array_merge($existingConversation->metadata ?? [], $conversationMetadata),
                ])->save();
            }

            return $existingConversation->load(['context', 'members.member']);
        }

        $memberUserIds = $userIms
            ->reject(fn (UserIm $userIm): bool => $userIm->id === $ownerUserIm->id)
            ->map(fn (UserIm $userIm): string => $this->externalUserId($userIm))
            ->values()
            ->all();

        $createPayload = [
            'type' => $conversationType->value,
            'subject' => $payload['subject'] ?? null,
            'owner_user_id' => $this->externalUserId($ownerUserIm),
            'scene' => 'rc_message',
            'metadata' => array_merge($conversationMetadata, [
                'conversation_key' => $conversationKey,
                'identity_ids' => array_keys($memberIdentities),
                'context_type' => $context?->getMorphClass(),
                'context_id' => $context?->getKey(),
            ]),
        ];

        if ($this->supportsInitialMembers($conversationType)) {
            $createPayload['member_user_ids'] = $memberUserIds;
        }

        $response = Im::conversation()->store($createPayload);

        $conversationNo = $this->resolveConversationNo($response);

        $conversation = DB::transaction(function () use ($context, $conversationKey, $conversationMetadata, $conversationNo, $conversationType, $ownerUserIm, $payload, $response, $userIms): ImConversation {
            $conversation = ImConversation::query()->firstOrCreate([
                'conversation_key' => $conversationKey,
            ], [
                'provider' => $ownerUserIm->provider,
                'app_code' => $ownerUserIm->app_code,
                'conversation_no' => $conversationNo,
                'conversation_type' => $conversationType,
                'owner_type' => 'rc_user_im',
                'owner_id' => $ownerUserIm->id,
                'context_type' => $context?->getMorphClass(),
                'context_id' => $context?->getKey(),
                'scene' => 'manual',
                'metadata' => array_merge($conversationMetadata, [
                    'subject' => $payload['subject'] ?? null,
                    'provider_response' => $response,
                ]),
            ]);

            foreach ($userIms as $userIm) {
                $this->syncConversationMember(
                    $conversation,
                    $userIm,
                    $userIm->id === $ownerUserIm->id ? 'owner' : 'member',
                );
            }

            return $conversation->load(['context', 'members.member']);
        });

        $this->sendInitialConversationMessage($identity, $ownerUserIm, $conversation);

        return $conversation;
    }

    public function resolveUserIm(UserIdentity $identity): UserIm
    {
        $userIm = UserIm::query()
            ->where('user_identity_id', $identity->id)
            ->first();

        if ($userIm instanceof UserIm) {
            return $userIm;
        }

        return $this->createOrUpdate($identity);
    }

    /**
     * 给特定 IM 用户发送系统通知。
     *
     * @param  array{
     *     notice_type?: string|null,
     *     title?: string|null,
     *     summary?: string|null,
     *     biz_id?: string|int|null,
     *     action_url?: string|null,
     *     client_msg_id?: string|null,
     *     metadata?: array<string, mixed>
     * }  $payload
     *
     * @throws \Throwable
     */
    public function sendSystemNotice(string $externalUserId, array $payload, ?ImSystemUser $systemUser = null): array
    {
        $receiver = UserIm::query()
            ->where('external_user_id', $externalUserId)
            ->first();

        if (! $receiver instanceof UserIm) {
            throw new InvalidArgumentException('接收人 IM 用户不存在。');
        }

        $systemUser ??= ImSystemUser::query()->active()->first();

        if (! $systemUser instanceof ImSystemUser) {
            throw new InvalidArgumentException('IM 系统用户不存在。');
        }

        if (blank($systemUser->external_user_id)) {
            throw new InvalidArgumentException('IM 系统用户外部标识不存在。');
        }

        $conversation = $this->resolveSystemConversation($receiver, $systemUser);
        $messagePayload = $this->systemNoticeMessagePayload($payload, $receiver, $systemUser);
        $message = Im::conversation()->postMessage($conversation->conversation_no, $messagePayload);

        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();

        return [
            'conversation' => $conversation->refresh(),
            'message' => $message,
            'payload' => $messagePayload,
        ];
    }

    private function externalUserId(UserIm $userIm): string
    {
        if (blank($userIm->external_user_id)) {
            throw new InvalidArgumentException('IM 用户标识不存在。');
        }

        return $userIm->external_user_id;
    }

    private function resolveConversationType(mixed $type): ImConversationType
    {
        $conversationType = $type instanceof ImConversationType
            ? $type
            : ImConversationType::tryFrom((string) $type);

        if (! $conversationType instanceof ImConversationType) {
            throw new InvalidArgumentException('会话类型无效。');
        }

        return $conversationType;
    }

    /**
     * @param  list<array{external_user_id: string}>  $members
     */
    private function validateInitialMembers(ImConversationType $conversationType, array $members): void
    {
        $memberCount = count($members);

        if ($conversationType === ImConversationType::Single && $memberCount !== 1) {
            throw new InvalidArgumentException('单聊会话只能初始化一名成员。');
        }

        if ($conversationType === ImConversationType::Group && $memberCount < 1) {
            throw new InvalidArgumentException('群聊会话至少需要初始化一名成员。');
        }

        if (! $this->supportsInitialMembers($conversationType) && $memberCount !== 0) {
            throw new InvalidArgumentException('聊天室和直播间不允许初始化成员。');
        }
    }

    private function supportsInitialMembers(ImConversationType $conversationType): bool
    {
        return in_array($conversationType, [
            ImConversationType::Single,
            ImConversationType::Group,
        ], true);
    }

    private function sendInitialConversationMessage(UserIdentity $identity, UserIm $ownerUserIm, ImConversation $conversation): void
    {
        $content = $this->initialConversationMessage($identity);

        if (blank($content)) {
            return;
        }

        Im::conversation()->postMessage($conversation->conversation_no, [
            'sender_user_id' => $this->externalUserId($ownerUserIm),
            'message_type' => 'text',
            'content' => [
                'card_type' => 'text',
                'text' => $content,
            ],
            'metadata' => [
                'source' => 'conversation_initial_message',
                'sender_user_im_id' => $ownerUserIm->id,
                'sender_user_identity_id' => $identity->id,
            ],
        ]);

        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();
    }

    private function initialConversationMessage(UserIdentity $identity): ?string
    {
        return match ($identity->identity_type) {
            RcIdentityType::JobSeeker => config('im.conversation.initial_messages.job_seeker'),
            RcIdentityType::Recruiter => config('im.conversation.initial_messages.recruiter'),
            default => null,
        };
    }

    /**
     * @throws \Throwable
     */
    private function resolveSystemConversation(UserIm $receiver, ImSystemUser $systemUser): ImConversation
    {
        $conversationKey = 'system:rc_user_im:'.$receiver->id;

        $conversation = ImConversation::query()
            ->where('conversation_key', $conversationKey)
            ->first();

        if ($conversation instanceof ImConversation) {
            $this->syncSystemConversationMembers($conversation, $receiver, $systemUser);

            return $conversation;
        }

        $response = Im::conversation()->store([
            'conversation_key' => $conversationKey,
            'type' => ImConversationType::Single->value,
            'scene' => 'system',
            'subject' => $systemUser->name,
            'owner_user_id' => $systemUser->external_user_id,
            'member_user_ids' => [
                $this->systemReceiverUserId($receiver),
            ],
            'metadata' => [
                'channel' => 'system_notice',
                'system' => true,
                'system_user_id' => $systemUser->id,
                'receiver_user_im_id' => $receiver->id,
            ],
        ]);
        $conversationNo = $this->resolveConversationNo($response);

        return DB::transaction(function () use ($conversationKey, $conversationNo, $receiver, $response, $systemUser): ImConversation {
            $conversation = ImConversation::query()->firstOrCreate([
                'conversation_key' => $conversationKey,
            ], [
                'provider' => $systemUser->provider,
                'app_code' => $systemUser->app_code,
                'conversation_no' => $conversationNo,
                'conversation_type' => ImConversationType::Single,
                'owner_type' => 'im_system_user',
                'owner_id' => $systemUser->id,
                'scene' => 'system',
                'metadata' => [
                    'channel' => 'system_notice',
                    'system' => true,
                    'system_user_id' => $systemUser->id,
                    'receiver_user_im_id' => $receiver->id,
                    'provider_response' => $response,
                ],
            ]);

            $this->syncSystemConversationMembers($conversation, $receiver, $systemUser);

            return $conversation;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function systemNoticeMessagePayload(array $payload, UserIm $receiver, ImSystemUser $systemUser): array
    {
        $metadata = $payload['metadata'] ?? [];

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $messagePayload = [
            'sender_user_id' => $systemUser->external_user_id,
            'message_type' => 'system_notice',
            'content' => [
                'notice_type' => $payload['notice_type'] ?? null,
                'title' => $payload['title'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'biz_id' => isset($payload['biz_id']) ? (string) $payload['biz_id'] : null,
                'action_url' => $payload['action_url'] ?? null,
            ],
            'metadata' => array_merge($metadata, [
                'source' => 'system_notice',
                'system_user_id' => $systemUser->id,
                'receiver_user_im_id' => $receiver->id,
            ]),
        ];

        if (filled($payload['client_msg_id'] ?? null)) {
            $messagePayload['client_msg_id'] = (string) $payload['client_msg_id'];
        }

        return $messagePayload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function conversationMetadata(array $payload): array
    {
        $metadata = $payload['metadata'] ?? [];

        if (! is_array($metadata)) {
            return [];
        }

        return array_diff_key($metadata, array_flip([
            'conversation_key',
            'identity_ids',
            'provider_response',
            'subject',
        ]));
    }

    private function resolveIdentityFromExternalUserId(string $externalUserId): UserIdentity
    {
        $identityId = (new Sqids(minLength: 32))->decode($externalUserId)[0] ?? 0;

        if ($identityId < 1) {
            throw new InvalidArgumentException('成员 IM 用户标识无效。');
        }

        $identity = UserIdentity::query()->find($identityId);

        if (! $identity instanceof UserIdentity) {
            throw new InvalidArgumentException('成员身份不存在。');
        }

        return $identity;
    }

    /**
     * @param  list<UserIm>  $userIms
     */
    private function conversationKey(ImConversationType $conversationType, array $userIms, ?Model $context = null): string
    {
        $memberKeys = array_map(
            static fn (UserIm $userIm): string => 'rc_user_im:'.$userIm->id,
            $userIms,
        );

        sort($memberKeys);

        $key = $conversationType->value.':'.implode('|', $memberKeys);

        if ($context instanceof Model) {
            $key .= ':context:'.$context->getMorphClass().':'.$context->getKey();
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveConversationContext(array $payload): ?Model
    {
        $jobId = (int) ($payload['job_id'] ?? 0);

        if ($jobId < 1) {
            return null;
        }

        $job = Job::query()->find($jobId);

        if (! $job instanceof Job) {
            throw new InvalidArgumentException('职位不存在。');
        }

        return $job;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function resolveConversationNo(array $response): string
    {
        $conversationNo = $response['conversation_no']
            ?? $response['conversation_id']
            ?? $response['id']
            ?? null;

        if (blank($conversationNo)) {
            throw new InvalidArgumentException('IM 会话创建失败。');
        }

        return (string) $conversationNo;
    }

    private function syncConversationMember(ImConversation $conversation, UserIm $userIm, string $role): void
    {
        ImConversationMember::query()->updateOrCreate([
            'conversation_id' => $conversation->id,
            'member_type' => 'rc_user_im',
            'member_id' => $userIm->id,
        ], [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    private function systemReceiverUserId(UserIm $receiver): string
    {
        return $receiver->external_user_id;
    }

    private function syncSystemConversationMembers(ImConversation $conversation, UserIm $receiver, ImSystemUser $systemUser): void
    {
        ImConversationMember::query()->updateOrCreate([
            'conversation_id' => $conversation->id,
            'member_type' => 'im_system_user',
            'member_id' => $systemUser->id,
        ], [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        ImConversationMember::query()->updateOrCreate([
            'conversation_id' => $conversation->id,
            'member_type' => 'rc_user_im',
            'member_id' => $receiver->id,
        ], [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }
}
