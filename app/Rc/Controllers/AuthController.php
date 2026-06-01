<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\PersonalAccessTokenResult;

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
        /** @var User $user */
        $user = $request->user();

        $validated = validator($request->all(), [
            'identity_type' => ['required', Rule::enum(RcIdentityType::class)],
        ])->validate();

        $identityType = RcIdentityType::from((int) $validated['identity_type']);

        $isFirstIdentity = ! $user->identities()->exists();

        /** @var UserIdentity $identity */
        $identity = $user->identities()->firstOrCreate(
            ['identity_type' => $identityType->value],
            [
                'identity_name' => $identityType->getLabel() ?? '身份',
                'is_default' => $isFirstIdentity ? 1 : 0,
                'status' => RcIdentityStatus::Enabled->value,
            ],
        );

        $tokenResult = $this->createRcToken($user);

        if ($token = $tokenResult->getToken()) {
            $token->responsible_type = UserIdentity::class;
            $token->responsible_id = $identity->id;
            $token->save();
        }

        if ($identity->is_default !== 1) {
            $user->identities()
                ->whereKey($identity->id)
                ->update(['is_default' => 1]);

            $user->identities()
                ->whereKeyNot($identity->id)
                ->where('is_default', 1)
                ->update(['is_default' => 0]);

            $identity->forceFill(['is_default' => 1]);
        }

        $user->token()?->revoke();

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $identity),
        ]);
    }

    private function respondWithToken(Request $request, User $user): JsonResponse
    {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $this->createRcToken($user);

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

    private function createRcToken(User $user): PersonalAccessTokenResult
    {
        return app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc',
            [],
            'rc_users',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, ?UserIdentity $userIdentity): array
    {
        $identities = $user->identities()
            ->withBasicInfoFlags()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->each(static function (UserIdentity $identity): void {
                $identity->append('has_basic_info');
            });

        $currentIdentity = null;

        if ($userIdentity instanceof UserIdentity) {
            $currentIdentity = $identities->firstWhere('id', $userIdentity->id)
                ?? $userIdentity->append('has_basic_info');
        }

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
            'current_identity' => $currentIdentity,
            'identities' => $identities,
        ];
    }
}
