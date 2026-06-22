<?php

namespace App\Services;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcNotificationType;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\Notification;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RcNotificationService extends Service
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginateForIdentity(
        User $user,
        ?UserIdentity $identity,
        int $perPage,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = $this->queryForIdentity($user, $identity);

        if (filled($filters['is_read'] ?? null)) {
            $isRead = filter_var($filters['is_read'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($isRead === true) {
                $query->whereNotNull('read_at');
            } elseif ($isRead === false) {
                $query->whereNull('read_at');
            }
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('type', (int) $filters['type']);
        }

        return $query
            ->orderByDesc('happened_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForIdentity(User $user, ?UserIdentity $identity, int $notificationId): ?Notification
    {
        return $this->queryForIdentity($user, $identity)
            ->whereKey($notificationId)
            ->first();
    }

    public function countUnread(User $user, ?UserIdentity $identity): int
    {
        return $this->queryForIdentity($user, $identity)
            ->unread()
            ->count();
    }

    public function markAsRead(User $user, Notification $notification): Notification
    {
        if ($notification->user_id !== $user->id) {
            throw new InvalidArgumentException('无权操作该通知。');
        }

        if ($notification->isRead()) {
            return $notification;
        }

        $notification->fill([
            'read_at' => now(),
        ]);
        $notification->save();

        return $notification->refresh()->load('userIdentity');
    }

    public function markAllAsRead(User $user, ?UserIdentity $identity): int
    {
        return $this->queryForIdentity($user, $identity)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    public function notifyInterviewInvitation(Application $application, Interview $interview): Notification
    {
        $context = $this->resolveApplicationContext($application);
        $recipientIdentity = $this->resolveJobSeekerIdentityForUser($application->candidate_user_id);

        return $this->create(
            userId: $application->candidate_user_id,
            type: RcNotificationType::InterviewInvitation,
            title: '面试邀请',
            body: sprintf('%s邀请您参加「%s」的面试', $context['company_name'], $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'interview_id' => $interview->id,
            ],
            recipientIdentity: $recipientIdentity,
        );
    }

    public function notifyOfferSent(Application $application): Notification
    {
        $context = $this->resolveApplicationContext($application);
        $recipientIdentity = $this->resolveJobSeekerIdentityForUser($application->candidate_user_id);

        return $this->create(
            userId: $application->candidate_user_id,
            type: RcNotificationType::OfferSent,
            title: 'Offer 通知',
            body: sprintf('%s已向您的「%s」投递发送 Offer', $context['company_name'], $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
            ],
            recipientIdentity: $recipientIdentity,
        );
    }

    public function notifyApplicationRejected(Application $application): Notification
    {
        $context = $this->resolveApplicationContext($application);
        $recipientIdentity = $this->resolveJobSeekerIdentityForUser($application->candidate_user_id);

        return $this->create(
            userId: $application->candidate_user_id,
            type: RcNotificationType::ApplicationStatusChanged,
            title: '投递状态变更',
            body: sprintf('您的「%s」投递已被 %s 淘汰', $context['job_title'], $context['company_name']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'status' => $application->status?->value,
            ],
            recipientIdentity: $recipientIdentity,
        );
    }

    public function notifyApplicationHired(Application $application): Notification
    {
        $context = $this->resolveApplicationContext($application);
        $recipientIdentity = $this->resolveJobSeekerIdentityForUser($application->candidate_user_id);

        return $this->create(
            userId: $application->candidate_user_id,
            type: RcNotificationType::ApplicationStatusChanged,
            title: '投递状态变更',
            body: sprintf('恭喜！%s 已录用您应聘的「%s」', $context['company_name'], $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'status' => $application->status?->value,
            ],
            recipientIdentity: $recipientIdentity,
        );
    }

    public function notifyInterviewInvitationAccepted(Application $application, Interview $interview): void
    {
        $context = $this->resolveApplicationContext($application);
        $candidateName = $this->resolveCandidateDisplayName($application);

        $this->notifyCompanyRecruiters(
            application: $application,
            type: RcNotificationType::InterviewInvitationAccepted,
            title: '面试邀请已接受',
            body: sprintf('%s已接受「%s」的面试邀请', $candidateName, $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'interview_id' => $interview->id,
                'status' => $application->status?->value,
            ],
        );
    }

    public function notifyInterviewInvitationRejected(Application $application, Interview $interview): void
    {
        $context = $this->resolveApplicationContext($application);
        $candidateName = $this->resolveCandidateDisplayName($application);

        $this->notifyCompanyRecruiters(
            application: $application,
            type: RcNotificationType::InterviewInvitationRejected,
            title: '面试邀请已拒绝',
            body: sprintf('%s已拒绝「%s」的面试邀请', $candidateName, $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'interview_id' => $interview->id,
                'status' => $application->status?->value,
            ],
        );
    }

    public function notifyOfferAcceptedByCandidate(Application $application): void
    {
        $context = $this->resolveApplicationContext($application);
        $candidateName = $this->resolveCandidateDisplayName($application);

        $this->notifyCompanyRecruiters(
            application: $application,
            type: RcNotificationType::OfferAcceptedByCandidate,
            title: 'Offer已接受',
            body: sprintf('%s已接受「%s」的 Offer', $candidateName, $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'status' => $application->status?->value,
            ],
        );
    }

    public function notifyOfferRejectedByCandidate(Application $application): void
    {
        $context = $this->resolveApplicationContext($application);
        $candidateName = $this->resolveCandidateDisplayName($application);

        $this->notifyCompanyRecruiters(
            application: $application,
            type: RcNotificationType::OfferRejectedByCandidate,
            title: 'Offer已拒绝',
            body: sprintf('%s已拒绝「%s」的 Offer', $candidateName, $context['job_title']),
            payload: [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->company_id,
                'status' => $application->status?->value,
            ],
        );
    }

    public function notifySchoolActivityCompanyInvited(SchoolActivityCompany $application): void
    {
        $context = $this->resolveSchoolActivityCompanyContext($application);

        $this->notifyCompanyRecruitersForActivityCompany(
            application: $application,
            type: RcNotificationType::SchoolActivityCompanyInvited,
            title: '校招活动邀约',
            body: sprintf(
                '%s邀请贵司参加「%s」',
                $context['organizer_name'],
                $context['activity_title'],
            ),
        );
    }

    public function notifySchoolActivityCompanyApproved(SchoolActivityCompany $application): void
    {
        $context = $this->resolveSchoolActivityCompanyContext($application);

        $this->notifyCompanyRecruitersForActivityCompany(
            application: $application,
            type: RcNotificationType::SchoolActivityCompanyApproved,
            title: '校招活动审批通过',
            body: sprintf(
                '%s已通过贵司参加「%s」的申请',
                $context['organizer_name'],
                $context['activity_title'],
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(
        int $userId,
        RcNotificationType $type,
        string $title,
        ?string $body = null,
        ?array $payload = null,
        ?Carbon $happenedAt = null,
        ?UserIdentity $recipientIdentity = null,
    ): Notification {
        return Notification::query()->create([
            'user_id' => $userId,
            'user_identity_id' => $recipientIdentity?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'happened_at' => $happenedAt ?? now(),
        ]);
    }

    /**
     * @return Builder<Notification>
     */
    private function queryForIdentity(User $user, ?UserIdentity $identity): Builder
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->visibleToIdentity($identity)
            ->with('userIdentity');
    }

    private function resolveJobSeekerIdentityForUser(int $userId): ?UserIdentity
    {
        return UserIdentity::query()
            ->where('user_id', $userId)
            ->where('identity_type', RcIdentityType::JobSeeker)
            ->where('status', RcIdentityStatus::Enabled)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, UserIdentity>
     */
    private function resolveRecruiterIdentitiesForCompany(int $companyId): Collection
    {
        return UserIdentity::query()
            ->where('identity_type', RcIdentityType::Recruiter)
            ->where('organization_type', 'company')
            ->where('organization_id', $companyId)
            ->where('status', RcIdentityStatus::Enabled)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notifyCompanyRecruiters(
        Application $application,
        RcNotificationType $type,
        string $title,
        string $body,
        array $payload,
    ): void {
        $identities = $this->resolveRecruiterIdentitiesForCompany($application->company_id);

        foreach ($identities as $identity) {
            $this->create(
                userId: $identity->user_id,
                type: $type,
                title: $title,
                body: $body,
                payload: $payload,
                recipientIdentity: $identity,
            );
        }
    }

    private function notifyCompanyRecruitersForActivityCompany(
        SchoolActivityCompany $application,
        RcNotificationType $type,
        string $title,
        string $body,
    ): void {
        $context = $this->resolveSchoolActivityCompanyContext($application);
        $payload = [
            'activity_id' => $context['activity_id'],
            'company_id' => $context['company_id'],
            'school_activity_company_id' => $application->id,
            'activity_booth_id' => $application->activity_booth_id,
            'join_source' => $application->join_source->value,
            'apply_status' => $application->apply_status->value,
        ];

        $identities = $this->resolveRecruiterIdentitiesForCompany($application->company_id);

        foreach ($identities as $identity) {
            $this->create(
                userId: $identity->user_id,
                type: $type,
                title: $title,
                body: $body,
                payload: $payload,
                recipientIdentity: $identity,
            );
        }
    }

    private function resolveCandidateDisplayName(Application $application): string
    {
        $application->loadMissing('resume');

        if (filled($application->resume?->full_name)) {
            return (string) $application->resume->full_name;
        }

        $snapshot = is_array($application->resume_snapshot) ? $application->resume_snapshot : [];
        $name = trim((string) ($snapshot['full_name'] ?? ''));

        return $name !== '' ? $name : '候选人';
    }

    /**
     * @return array{company_name: string, job_title: string}
     */
    private function resolveApplicationContext(Application $application): array
    {
        $application->loadMissing(['job', 'company']);

        return [
            'company_name' => $application->company?->name ?? '企业',
            'job_title' => $application->job?->title ?? '职位',
        ];
    }

    /**
     * @return array{
     *     organizer_name: string,
     *     activity_title: string,
     *     activity_id: int,
     *     company_id: int,
     *     company_name: string
     * }
     */
    private function resolveSchoolActivityCompanyContext(SchoolActivityCompany $application): array
    {
        $application->loadMissing(['activity.organizer', 'company']);

        $organizerName = '院校';

        if ($application->activity?->organizer_type === RcSchoolActivityOrganizerType::School && $application->activity->organizer) {
            $organizerName = (string) ($application->activity->organizer->name ?? '院校');
        }

        return [
            'organizer_name' => $organizerName,
            'activity_title' => (string) ($application->activity?->title ?? '校招活动'),
            'activity_id' => (int) $application->activity_id,
            'company_id' => (int) $application->company_id,
            'company_name' => (string) ($application->company?->name ?? '企业'),
        ];
    }
}
