<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\EmailLoginRequest;
use App\Rc\Requests\ForgotPasswordRequest;
use App\Rc\Requests\PhoneLoginRequest;
use App\Rc\Requests\SendVerificationCodeRequest;
use App\Services\UserService;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * 发送验证码接口
     *
     * POST /rc/auth/send-verification-code
     *
     * @throws \Exception
     */
    public function sendVerificationCode(SendVerificationCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = (string) $validated['type'];
        $account = (string) $validated['account'];
        $scene = (string) $validated['scene'];

        $result = VerificationCodeService::make()->send($type, $account, $scene);

        if (! $result['sent']) {
            return $this->error((string) $result['message'], Response::HTTP_TOO_MANY_REQUESTS, [
                'expires_in' => $result['expires_in'],
            ]);
        }

        return $this->success();
    }

    /**
     * 手机号登录接口
     *
     * POST /rc/auth/phone-login
     */
    public function phoneLogin(PhoneLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = (string) $validated['phone'];

        if (! VerificationCodeService::make()->verify('phone', $phone, 'login', (string) $validated['code'])) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            $user = UserService::make()->register([
                'phone' => $phone,
            ]);
        }

        return $this->respondWithToken($request, $user);
    }

    /**
     * 邮箱登录接口
     *
     * POST /rc/auth/email-login
     */
    public function emailLogin(EmailLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = mb_strtolower((string) $validated['email']);

        if (! VerificationCodeService::make()->verify('email', $email, 'login', (string) $validated['code'])) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = UserService::make()->register([
                'email' => $email,
            ]);
        }

        return $this->respondWithToken($request, $user);
    }

    /**
     * 用户信息
     *
     * GET /rc/auth/me
     *
     * @throws \Exception
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'user' => $this->userPayload($user, $user->token()?->responsible),
        ]);
    }

    /**
     * 退出登录
     *
     * POST /rc/auth/logout
     *
     * @throws \Exception
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->token()?->revoke();

        return $this->success();
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = (string) $validated['type'];
        $account = (string) $validated['account'];
        $code = (string) $validated['code'];

        if (! VerificationCodeService::make()->verify($type, $account, 'forgot_password', $code)) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->findUserByType($type, $account);

        if (! $user) {
            return $this->error('用户不存在。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        return $this->success();
    }

    /**
     * 刷新token
     *
     * POST /rc/auth/refresh-token
     *
     * @throws \Exception
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        $tokenResult = $user->createToken('rc');

        $user->token()?->revoke();

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $user->token()?->responsible),
        ]);
    }

    private function respondWithToken(Request $request, User $user): JsonResponse
    {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $user->createToken('rc');

        $userIdentity = $user->defaultIdentity()->first();

        if ($userIdentity) {
            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = UserIdentity::class;
                $token->responsible_id = $userIdentity->id;
                $token->save();
            }
        }

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $userIdentity),
        ]);
    }

    private function findUserByType(string $type, string $account): ?User
    {
        return User::query()
            ->when(
                $type === 'email',
                fn ($query) => $query->where('email', mb_strtolower(trim($account))),
                fn ($query) => $query->where('phone', trim($account)),
            )
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, ?UserIdentity $userIdentity): array
    {
        $identityContext = $this->buildIdentityContext($user, $userIdentity);

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'nickname' => $user->nickname,
            'phone' => $user->phone,
            'email' => $user->email,
            'gender' => $user->gender,
            'avatar' => $user->avatar,
            'last_login_ip' => $user->last_login_ip,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'current_identity' => $userIdentity,
            'resume' => $identityContext['resume'],
            'companies' => $identityContext['companies'],
            'school' => $identityContext['school'],
            'city_code' => $identityContext['city_code'],
            'next_action' => $identityContext['next_action'],
            'can_publish_job' => $identityContext['can_publish_job'],
        ];
    }

    /**
     * @return array{
     *     resume: array<string, mixed>|null,
     *     companies: array<int, array<string, mixed>>|null,
     *     school: array<string, mixed>|null,
     *     city_code: string|null,
     *     next_action: string|null,
     *     can_publish_job: bool
     * }
     */
    private function buildIdentityContext(User $user, ?UserIdentity $identity): array
    {
        $resume = null;
        $companies = null;
        $school = null;
        $cityCode = null;
        $nextAction = null;
        $canPublishJob = false;

        if (! $identity instanceof UserIdentity) {
            return [
                'resume' => null,
                'companies' => null,
                'school' => null,
                'city_code' => null,
                'next_action' => 'choose_identity',
                'can_publish_job' => false,
            ];
        }

        $identityType = $identity->identity_type instanceof RcIdentityType
            ? $identity->identity_type
            : RcIdentityType::tryFrom((int) $identity->identity_type);

        return match ($identityType) {
            RcIdentityType::JobSeeker => (function () use ($user): array {
                $primaryResume = $user->primaryResume()
                    ->select(['id', 'title', 'status', 'updated_at'])
                    ->first();

                $fallbackResume = $primaryResume
                    ?? $user->resumes()
                        ->select(['id', 'title', 'status', 'updated_at'])
                        ->orderByDesc('updated_at')
                        ->first();

                $resumePayload = $fallbackResume instanceof Resume
                    ? [
                        'id' => $fallbackResume->id,
                        'title' => $fallbackResume->title,
                        'status' => $fallbackResume->status,
                        'updated_at' => $fallbackResume->updated_at?->toDateTimeString(),
                    ]
                    : null;

                return [
                    'resume' => $resumePayload,
                    'companies' => null,
                    'school' => null,
                    'city_code' => null,
                    'next_action' => $resumePayload ? null : 'fill_resume',
                    'can_publish_job' => false,
                ];
            })(),
            RcIdentityType::Recruiter => (function () use ($identity): array {
                $companiesQuery = Company::query()
                    ->select(['id', 'name'])
                    ->when(
                        filled($identity->company_id),
                        fn ($query) => $query->whereKey((int) $identity->company_id),
                    );

                $companyPayload = $companiesQuery->get()->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'name' => $company->name,
                ])->values()->all();

                if ($companyPayload === []) {
                    $companyPayload = null;
                }

                return [
                    'resume' => null,
                    'companies' => $companyPayload,
                    'school' => null,
                    'city_code' => null,
                    'next_action' => $companyPayload ? null : 'bind_company',
                    'can_publish_job' => $companyPayload !== null,
                ];
            })(),
            RcIdentityType::CampusManager => (function () use ($identity): array {
                $schoolName = (string) ($identity->organization_name ?? Arr::get($identity->extra ?? [], 'school_name', ''));
                $schoolCode = Arr::get($identity->extra ?? [], 'school_code');

                $schoolPayload = filled($schoolName) || filled($schoolCode)
                    ? [
                        'name' => $schoolName !== '' ? $schoolName : null,
                        'code' => is_string($schoolCode) && $schoolCode !== '' ? $schoolCode : null,
                    ]
                    : null;

                return [
                    'resume' => null,
                    'companies' => null,
                    'school' => $schoolPayload,
                    'city_code' => null,
                    'next_action' => $schoolPayload ? null : 'bind_school',
                    'can_publish_job' => false,
                ];
            })(),
            RcIdentityType::GovernmentManager => (function () use ($identity): array {
                $resolvedCityCode = Arr::get($identity->extra ?? [], 'city_code');

                if (! is_string($resolvedCityCode) || $resolvedCityCode === '') {
                    $resolvedCityCode = null;
                }

                return [
                    'resume' => null,
                    'companies' => null,
                    'school' => null,
                    'city_code' => $resolvedCityCode,
                    'next_action' => $resolvedCityCode ? null : 'bind_city',
                    'can_publish_job' => false,
                ];
            })(),
            RcIdentityType::Headhunter => [
                'resume' => null,
                'companies' => null,
                'school' => null,
                'city_code' => null,
                'next_action' => null,
                'can_publish_job' => true,
            ],
            default => [
                'resume' => null,
                'companies' => null,
                'school' => null,
                'city_code' => null,
                'next_action' => 'choose_identity',
                'can_publish_job' => false,
            ],
        };
    }
}
