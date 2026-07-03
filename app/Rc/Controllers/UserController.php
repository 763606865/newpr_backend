<?php

namespace App\Rc\Controllers;

use App\Enums\RcInterviewStatus;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\UserPhoneLookupRequest;
use App\Rc\Requests\UserPhoneUpdateRequest;
use App\Rc\Requests\UserPhoneVerificationCodeRequest;
use App\Services\RcIdentityOrganizationService;
use App\Services\RcViewStatsService;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * 手机号检索
     *
     * GET /rc/users/phone/lookup
     *
     * 检查手机号是否已存在，以及是否可用于当前登录用户修改手机号。
     */
    public function lookupPhone(UserPhoneLookupRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $phone = (string) $request->validated('phone');
        $matchedUser = User::query()
            ->where('phone', $phone)
            ->first(['id', 'phone']);
        $isCurrentUser = $matchedUser?->is($user) ?? false;

        return $this->success([
            'phone' => $phone,
            'exists' => $matchedUser !== null,
            'available' => $matchedUser === null || $isCurrentUser,
            'is_current_user' => $isCurrentUser,
        ]);
    }

    /**
     * 发送修改手机号验证码
     *
     * POST /rc/users/phone/verification-code
     *
     * 向目标手机号发送修改手机号验证码；目标手机号不能属于其他用户。
     *
     * @throws \Exception
     */
    public function sendPhoneVerificationCode(UserPhoneVerificationCodeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $phone = (string) $request->validated('phone');

        if ($this->phoneBelongsToAnotherUser($phone, $user)) {
            return $this->error('手机号已被其他用户使用。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = VerificationCodeService::make()->send('phone', $phone, 'change_phone');

        if (! $result['sent']) {
            return $this->error((string) $result['message'], Response::HTTP_TOO_MANY_REQUESTS, [
                'expires_in' => $result['expires_in'],
            ]);
        }

        return $this->success();
    }

    /**
     * 修改手机号
     *
     * PUT /rc/users/phone
     *
     * 校验短信验证码并修改当前登录用户手机号。
     */
    public function updatePhone(UserPhoneUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $phone = (string) $validated['phone'];

        if ($phone === $user->phone) {
            return $this->error('新手机号不能与当前手机号相同。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->phoneBelongsToAnotherUser($phone, $user)) {
            return $this->error('手机号已被其他用户使用。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! VerificationCodeService::make()->verify('phone', $phone, 'change_phone', (string) $validated['code'])) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill([
            'phone' => $phone,
        ])->save();

        return $this->success([
            'phone' => $user->phone,
        ]);
    }

    /**
     * 求职者统计数据
     *
     * GET /rc/users/jobseeker/stats
     *
     * 仅求职者身份可访问；返回投递数、待面试数、收藏职位数和简历查看数。
     */
    public function jobSeekerStats(): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $resumeIds = Resume::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->map(fn (mixed $resumeId): int => (int) $resumeId)
            ->all();

        $resumeViewTotals = RcViewStatsService::make()->getResumeTotalViewsForIds($resumeIds);

        return $this->success([
            'applications' => Application::query()
                ->where('candidate_user_id', $user->id)
                ->count(),
            'pending_interviews' => Interview::query()
                ->whereIn('status', [
                    RcInterviewStatus::AwaitingCandidate->value,
                    RcInterviewStatus::Scheduled->value,
                ])
                ->whereHas('application', function ($query) use ($user): void {
                    $query->where('candidate_user_id', $user->id);
                })
                ->count(),
            'favorite_jobs' => JobFavorite::query()
                ->where('user_id', $user->id)
                ->count(),
            'resume_views' => array_sum($resumeViewTotals),
        ]);
    }

    private function phoneBelongsToAnotherUser(string $phone, User $user): bool
    {
        return User::query()
            ->where('phone', $phone)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    /**
     * 解析当前用户的求职者身份。
     */
    private function resolveJobSeekerIdentity(User $user): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($user);
    }

    /**
     * 非求职者身份访问时的统一业务错误响应。
     */
    private function jobSeekerIdentityRequiredResponse(): JsonResponse
    {
        return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
