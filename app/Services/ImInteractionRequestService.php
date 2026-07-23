<?php

namespace App\Services;

use App\Enums\ImInteractionRequestStatus;
use App\Enums\ImInteractionRequestType;
use App\Enums\RcIdentityType;
use App\Libs\Facades\Im;
use App\Models\ImConversation;
use App\Models\ImInteractionRequest;
use App\Models\Rc\Application;
use App\Models\Rc\UserIm;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ImInteractionRequestService extends Service
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{interaction_request: ImInteractionRequest, message: array<string, mixed>, card: array<string, mixed>}
     *
     * @throws \Throwable
     */
    public function create(UserIm $senderUserIm, array $payload): array
    {
        $conversation = $this->findConversationForMember((int) $payload['conversation_id'], $senderUserIm);

        if (! $conversation instanceof ImConversation) {
            throw new InvalidArgumentException('会话不存在。');
        }

        $receiverUserIm = UserIm::query()->find((int) $payload['receiver_user_im_id']);

        if (! $receiverUserIm instanceof UserIm || ! $this->isConversationMember($conversation, $receiverUserIm)) {
            throw new InvalidArgumentException('接收方不在当前会话中。');
        }

        if ($receiverUserIm->id === $senderUserIm->id) {
            throw new InvalidArgumentException('不能向自己发起交互请求。');
        }

        $type = ImInteractionRequestType::from((string) $payload['type']);

        $existingPendingRequest = ImInteractionRequest::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_user_im_id', $senderUserIm->id)
            ->where('receiver_user_im_id', $receiverUserIm->id)
            ->where('type', $type)
            ->where('status', ImInteractionRequestStatus::Pending)
            ->first();

        if ($existingPendingRequest instanceof ImInteractionRequest) {
            throw new InvalidArgumentException('已存在待处理的交互请求。');
        }

        $interactionRequest = ImInteractionRequest::query()->create([
            'conversation_id' => $conversation->id,
            'sender_user_im_id' => $senderUserIm->id,
            'receiver_user_im_id' => $receiverUserIm->id,
            'type' => $type,
            'status' => ImInteractionRequestStatus::Pending,
            'payload' => $payload['payload'] ?? [],
            'expires_at' => $payload['expires_at'] ?? null,
        ]);

        $messagePayload = $this->requestMessagePayload($interactionRequest->refresh(), $senderUserIm);
        $message = Im::conversation()->postMessage($conversation->conversation_no, $messagePayload);

        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();

        return [
            'interaction_request' => $interactionRequest->refresh(),
            'message' => $message,
            'card' => $messagePayload['content'],
        ];
    }

    /**
     * @return array{interaction_request: ImInteractionRequest, message: array<string, mixed>|null, card: array<string, mixed>|null}
     *
     * @throws \Throwable
     */
    public function respond(UserIm $actorUserIm, ImInteractionRequest $interactionRequest, string $action, ?string $reason = null): array
    {
        if ($interactionRequest->receiver_user_im_id !== $actorUserIm->id) {
            throw new InvalidArgumentException('只有接收方可以处理该请求。');
        }

        if ($interactionRequest->status !== ImInteractionRequestStatus::Pending) {
            return [
                'interaction_request' => $interactionRequest,
                'message' => null,
                'card' => null,
            ];
        }

        if ($interactionRequest->expires_at !== null && $interactionRequest->expires_at->isPast()) {
            $interactionRequest->forceFill([
                'status' => ImInteractionRequestStatus::Expired,
                'responded_at' => now(),
            ])->save();

            throw new InvalidArgumentException('交互请求已过期。');
        }

        $status = $action === 'accept'
            ? ImInteractionRequestStatus::Accepted
            : ImInteractionRequestStatus::Rejected;

        $resultPayload = $this->resultPayload($interactionRequest, $status, $reason);

        $messagePayload = $this->resultMessagePayload($interactionRequest, $actorUserIm, $status, $resultPayload);
        $message = Im::conversation()->postMessage($interactionRequest->conversation->conversation_no, $messagePayload);

        DB::transaction(function () use ($interactionRequest, $resultPayload, $status): void {
            $interactionRequest->forceFill([
                'status' => $status,
                'result_payload' => $resultPayload,
                'responded_at' => now(),
            ])->save();

            $interactionRequest->conversation->forceFill([
                'last_message_at' => now(),
            ])->save();
        });

        return [
            'interaction_request' => $interactionRequest->refresh(),
            'message' => $message,
            'card' => $messagePayload['content'],
        ];
    }

    private function findConversationForMember(int $id, UserIm $userIm): ?ImConversation
    {
        return ImConversation::query()
            ->whereKey($id)
            ->whereHas('members', function ($query) use ($userIm): void {
                $query
                    ->where('member_type', 'rc_user_im')
                    ->where('member_id', $userIm->id);
            })
            ->first();
    }

    private function isConversationMember(ImConversation $conversation, UserIm $userIm): bool
    {
        return $conversation->members()
            ->where('member_type', 'rc_user_im')
            ->where('member_id', $userIm->id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestMessagePayload(ImInteractionRequest $interactionRequest, UserIm $senderUserIm): array
    {
        return [
            'sender_user_id' => $this->externalUserId($senderUserIm),
            'message_type' => 'interaction_request',
            'content' => [
                'interaction_request_id' => $interactionRequest->id,
                'type' => $interactionRequest->type->value,
                'type_label' => $interactionRequest->type->getLabel(),
                'title' => $interactionRequest->type->defaultTitle(),
                'summary' => $this->requestSummary($interactionRequest->type),
                'status' => $interactionRequest->status->value,
                'actions' => ['accept', 'reject'],
                'payload' => $interactionRequest->payload ?? [],
            ],
            'metadata' => [
                'source' => 'im_interaction_request',
                'interaction_request_id' => $interactionRequest->id,
                'sender_user_im_id' => $interactionRequest->sender_user_im_id,
                'receiver_user_im_id' => $interactionRequest->receiver_user_im_id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $resultPayload
     * @return array<string, mixed>
     */
    private function resultMessagePayload(ImInteractionRequest $interactionRequest, UserIm $actorUserIm, ImInteractionRequestStatus $status, array $resultPayload): array
    {
        return [
            'sender_user_id' => $this->externalUserId($actorUserIm),
            'message_type' => 'interaction_result',
            'client_msg_id' => 'im_interaction_request_'.$interactionRequest->id.'_'.$status->value,
            'content' => [
                'interaction_request_id' => $interactionRequest->id,
                'type' => $interactionRequest->type->value,
                'type_label' => $interactionRequest->type->getLabel(),
                'title' => $interactionRequest->type->defaultTitle(),
                'status' => $status->value,
                'status_label' => $status->getLabel(),
                'result' => $resultPayload,
            ],
            'metadata' => [
                'source' => 'im_interaction_result',
                'interaction_request_id' => $interactionRequest->id,
                'sender_user_im_id' => $interactionRequest->sender_user_im_id,
                'receiver_user_im_id' => $interactionRequest->receiver_user_im_id,
                'actor_user_im_id' => $actorUserIm->id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultPayload(ImInteractionRequest $interactionRequest, ImInteractionRequestStatus $status, ?string $reason): array
    {
        if ($status === ImInteractionRequestStatus::Rejected && $interactionRequest->type === ImInteractionRequestType::ExchangeContact) {
            return [
                'reason' => $reason,
            ];
        }

        return match ($interactionRequest->type) {
            ImInteractionRequestType::ExchangeContact => $this->exchangeContactResultPayload($interactionRequest),
            ImInteractionRequestType::RespondInterviewInvitation => $this->respondInterviewInvitationResultPayload($interactionRequest, $status, $reason),
            ImInteractionRequestType::RespondOffer => $this->respondOfferResultPayload($interactionRequest, $status, $reason),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function exchangeContactResultPayload(ImInteractionRequest $interactionRequest): array
    {
        $sender = $interactionRequest->senderUserIm()->with('user')->first();
        $receiver = $interactionRequest->receiverUserIm()->with('user')->first();

        if (! $sender instanceof UserIm || ! $receiver instanceof UserIm) {
            throw new InvalidArgumentException('交互请求用户不存在。');
        }

        if (blank($sender->user?->phone) || blank($receiver->user?->phone)) {
            throw new InvalidArgumentException('双方手机号不完整，无法交换联系方式。');
        }

        return [
            'contacts' => [
                [
                    'user_im_id' => $sender->id,
                    'user_identity_id' => $sender->user_identity_id,
                    'phone' => $sender->user->phone,
                ],
                [
                    'user_im_id' => $receiver->id,
                    'user_identity_id' => $receiver->user_identity_id,
                    'phone' => $receiver->user->phone,
                ],
            ],
        ];
    }

    private function requestSummary(ImInteractionRequestType $type): string
    {
        return match ($type) {
            ImInteractionRequestType::ExchangeContact => '对方希望与你交换手机号。',
            ImInteractionRequestType::RespondInterviewInvitation => '你收到一条面试邀请，请确认是否参加。',
            ImInteractionRequestType::RespondOffer => '你收到一份 Offer，请确认是否接受。',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function respondInterviewInvitationResultPayload(ImInteractionRequest $interactionRequest, ImInteractionRequestStatus $status, ?string $reason): array
    {
        $application = $this->resolveCandidateApplication($interactionRequest);
        $service = RcApplicationService::make();
        $user = $interactionRequest->receiverUserIm->user;

        $application = $status === ImInteractionRequestStatus::Accepted
            ? $service->acceptInterviewInvitation($user, $application)
            : $service->rejectInterviewInvitation($user, $application, $reason);

        return $this->applicationResultPayload($application, $reason);
    }

    /**
     * @return array<string, mixed>
     */
    private function respondOfferResultPayload(ImInteractionRequest $interactionRequest, ImInteractionRequestStatus $status, ?string $reason): array
    {
        $application = $this->resolveCandidateApplication($interactionRequest);
        $service = RcApplicationService::make();
        $user = $interactionRequest->receiverUserIm->user;

        $application = $status === ImInteractionRequestStatus::Accepted
            ? $service->acceptOfferInvitation($user, $application, $reason)
            : $service->rejectOfferInvitation($user, $application, $reason);

        return $this->applicationResultPayload($application, $reason);
    }

    private function resolveCandidateApplication(ImInteractionRequest $interactionRequest): Application
    {
        $receiverUserIm = $interactionRequest->receiverUserIm;

        if ($receiverUserIm->identity_type !== RcIdentityType::JobSeeker) {
            throw new InvalidArgumentException('请先切换为求职者身份。');
        }

        $applicationId = (int) ($interactionRequest->payload['application_id'] ?? 0);

        if ($applicationId < 1) {
            throw new InvalidArgumentException('投递记录不能为空。');
        }

        $application = RcApplicationService::make()->findForCandidate($receiverUserIm->user, $applicationId);

        if (! $application instanceof Application) {
            throw new InvalidArgumentException('投递记录不存在。');
        }

        return $application;
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationResultPayload(Application $application, ?string $reason): array
    {
        return [
            'application' => [
                'id' => $application->id,
                'company_id' => $application->company_id,
                'job_id' => $application->job_id,
                'resume_id' => $application->resume_id,
                'candidate_user_id' => $application->candidate_user_id,
                'status' => $application->status?->value,
                'status_label' => $application->status?->getLabel(),
            ],
            'reason' => $reason,
        ];
    }

    private function externalUserId(UserIm $userIm): string
    {
        if (blank($userIm->external_user_id)) {
            throw new InvalidArgumentException('IM 用户标识不存在。');
        }

        return $userIm->external_user_id;
    }
}
