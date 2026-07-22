<?php

namespace App\Resources\Rc;

use App\Models\ImConversation;
use App\Models\ImConversationMember;
use App\Models\ImSystemUser;
use App\Models\Rc\Job;
use App\Models\Rc\UserIm;
use App\Models\User;
use App\Services\RcJobFavoriteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ImConversation) {
            return (array) $this->resource;
        }

        $members = $this->resource->relationLoaded('members')
            ? $this->resource->members->map(fn (ImConversationMember $member): array => $this->memberPayload($member))->values()
            : collect();
        $currentUserImId = (int) $request->attributes->get('current_user_im_id', 0);

        return [
            'id' => $this->resource->id,
            'provider' => $this->resource->provider,
            'app_code' => $this->resource->app_code,
            'conversation_no' => $this->resource->conversation_no,
            'conversation_type' => $this->resource->conversation_type?->value,
            'conversation_type_label' => $this->resource->conversation_type?->getLabel(),
            'conversation_key' => $this->resource->conversation_key,
            'owner_type' => $this->resource->owner_type,
            'owner_id' => $this->resource->owner_id,
            'context_type' => $this->resource->context_type,
            'context_id' => $this->resource->context_id,
            'context' => $this->contextPayload($request),
            'scene' => $this->resource->scene,
            'metadata' => $this->resource->metadata,
            'last_message_at' => $this->resource->last_message_at,
            'expires_at' => $this->resource->expires_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'members' => $members->all(),
            'participants' => $members->pluck('member')->filter()->values()->all(),
            'other_participants' => $members
                ->reject(fn (array $member): bool => $member['member_type'] === 'rc_user_im' && (int) $member['member_id'] === $currentUserImId)
                ->pluck('member')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(ImConversationMember $member): array
    {
        $memberModel = $member->relationLoaded('member') ? $member->member : null;

        return [
            'id' => $member->id,
            'member_type' => $member->member_type,
            'member_id' => $member->member_id,
            'role' => $member->role,
            'joined_at' => $member->joined_at,
            'last_read_at' => $member->last_read_at,
            'settings' => $member->settings,
            'member' => $this->memberModelPayload($memberModel),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function memberModelPayload(mixed $memberModel): ?array
    {
        if ($memberModel instanceof UserIm) {
            return [
                'id' => $memberModel->id,
                'type' => 'user',
                'user_id' => $memberModel->user_id,
                'user_identity_id' => $memberModel->user_identity_id,
                'identity_type' => $memberModel->identity_type?->value,
                'provider' => $memberModel->provider,
                'app_code' => $memberModel->app_code,
                'external_user_id' => $memberModel->external_user_id,
                'im_user_id' => $memberModel->im_user_id,
                'user' => $memberModel->relationLoaded('user') && $memberModel->user instanceof User ? [
                    'id' => $memberModel->user->id,
                    'name' => $memberModel->user->name,
                    'nickname' => $memberModel->user->nickname,
                    'mask_name' => $memberModel->user->mask_name,
                    'avatar' => $memberModel->user->avatar,
                    'display_avatar' => $memberModel->user->display_avatar,
                ] : null,
                'identity' => $memberModel->relationLoaded('userIdentity') && $memberModel->userIdentity ? [
                    'id' => $memberModel->userIdentity->id,
                    'identity_type' => $memberModel->userIdentity->identity_type?->value,
                    'identity_name' => $memberModel->userIdentity->identity_name,
                    'organization_type' => $memberModel->userIdentity->organization_type,
                    'organization_id' => $memberModel->userIdentity->organization_id,
                    'organization_name' => $memberModel->userIdentity->organization_name,
                    'job_title' => $memberModel->userIdentity->job_title,
                ] : null,
            ];
        }

        if ($memberModel instanceof ImSystemUser) {
            return [
                'id' => $memberModel->id,
                'type' => 'system',
                'code' => $memberModel->code,
                'name' => $memberModel->name,
                'provider' => $memberModel->provider,
                'app_code' => $memberModel->app_code,
                'external_user_id' => $memberModel->external_user_id,
                'im_user_id' => $memberModel->im_user_id,
                'avatar' => $memberModel->avatar,
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function contextPayload(Request $request): ?array
    {
        $context = $this->resource->relationLoaded('context') ? $this->resource->context : null;
        $viewer = $request->user('rc');

        if ($context instanceof Job) {
            return [
                'type' => 'job',
                'id' => $context->id,
                'company_id' => $context->company_id,
                'title' => $context->title,
                'employment_type' => $context->employment_type?->value,
                'employment_type_label' => $context->employment_type?->getLabel(),
                'city_code' => $context->city_code,
                'workplace' => $context->workplace,
                'salary_min' => $context->salary_min,
                'salary_max' => $context->salary_max,
                'salary_unit' => $context->salary_unit?->value,
                'salary_unit_label' => $context->salary_unit?->getLabel(),
                'annual_salary_months' => $context->annual_salary_months,
                'benefit' => $context->benefit,
                'status' => $context->status?->value,
                'status_label' => $context->status?->getLabel(),
                'is_favorited' => $viewer instanceof User
                    && RcJobFavoriteService::make()->isFavorited($viewer, $context->id),
                'published_at' => $context->published_at,
            ];
        }

        return null;
    }
}
