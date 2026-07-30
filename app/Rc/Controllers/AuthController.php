<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\EmailLoginRequest;
use App\Rc\Requests\ForgotPasswordRequest;
use App\Rc\Requests\PhoneLoginRequest;
use App\Rc\Requests\RefreshTokenRequest;
use App\Rc\Requests\SendVerificationCodeRequest;
use App\Rc\Requests\WechatAppLoginRequest;
use App\Rc\Requests\WechatBindPhoneRequest;
use App\Rc\Requests\WechatMiniLoginRequest;
use App\Services\RcIdentityOrganizationService;
use App\Services\UserService;
use App\Services\VerificationCodeService;
use App\Services\WechatAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\PersonalAccessTokenResult;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    /**
     * 微信小程序登录。
     *
     * 使用 wx.login 凭证获取微信身份，并通过 getPhoneNumber 凭证获取可信手机号。
     *
     * POST /rc/auth/wechat-mini-login
     */
    public function wechatMiniLogin(WechatMiniLoginRequest $request, WechatAuthService $service): JsonResponse
    {
        try {
            $user = $service->loginMini($request->validated());

            return $this->respondWithToken($request, $user);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('微信服务暂时不可用，请稍后重试。', Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * 微信 App 授权登录。
     *
     * 已绑定手机号时直接登录，否则返回短时 pending_token 进入手机号绑定流程。
     *
     * POST /rc/auth/wechat-app-login
     */
    public function wechatAppLogin(WechatAppLoginRequest $request, WechatAuthService $service): JsonResponse
    {
        try {
            $result = $service->loginApp($request->validated());

            if (! $result['user'] instanceof User) {
                return $this->success([
                    'status' => 'need_phone',
                    'pending_token' => $result['pending_token'],
                    'token_type' => null,
                    'access_token' => null,
                    'user' => null,
                ]);
            }

            return $this->respondWithToken($request, $result['user'], status: 'ok');
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('微信服务暂时不可用，请稍后重试。', Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * 为微信 App 待登录身份绑定已验证手机号。
     *
     * POST /rc/auth/wechat-bind-phone
     */
    public function wechatBindPhone(WechatBindPhoneRequest $request, WechatAuthService $service): JsonResponse
    {
        $validated = $request->validated();
        $phone = (string) $validated['phone'];
        $pendingToken = (string) $validated['pending_token'];

        if (! $service->hasPendingToken($pendingToken)) {
            return $this->error('微信登录状态已失效，请重新授权。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! VerificationCodeService::make()->verify('phone', $phone, 'bind', (string) $validated['code'])) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $service->bindAppPhone($pendingToken, $phone);

            return $this->respondWithToken($request, $user);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

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
        $loginIdentity = null;

        // 带身份类型登录
        if ($request->has('rc_user_identity_type')) {
            $identityType = RcIdentityType::from((int) $validated['rc_user_identity_type']);
            $isFirstIdentity = ! $user->identities()->exists();

            /** @var UserIdentity $loginIdentity */
            $loginIdentity = $user->identities()->firstOrCreate(
                ['identity_type' => $identityType->value],
                [
                    'identity_name' => $identityType->getLabel() ?? '身份',
                    'is_default' => $isFirstIdentity ? 1 : 0,
                    'status' => RcIdentityStatus::Enabled->value,
                ],
            );

            $this->setDefaultIdentity($user, $loginIdentity);
        }

        return $this->respondWithToken($request, $user, $loginIdentity);
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
     * 当前身份下已绑定的全部机构
     *
     * GET /rc/auth/organizations
     */
    public function organizations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $service = RcIdentityOrganizationService::make();
        $currentIdentity = $service->resolveCurrentIdentity($user);

        if (! $currentIdentity instanceof UserIdentity) {
            return $this->error('请先切换身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success($service->listForIdentity($user, $currentIdentity));
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

    /**
     * 忘记密码
     *
     * POST /rc/auth/forgot-password
     */
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
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        if (array_key_exists('identity_id', $validated)) {
            /** @var UserIdentity $identity */
            $identity = $user->identities()->findOrFail((int) $validated['identity_id']);

            if ($identity->status !== RcIdentityStatus::Enabled) {
                return $this->error('该身份已停用。', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
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
        }

        $tokenResult = $this->createRcToken($user);

        if ($token = $tokenResult->getToken()) {
            $token->responsible_type = UserIdentity::class;
            $token->responsible_id = $identity->id;
            $token->save();
        }

        $this->setDefaultIdentity($user, $identity);

        $user->token()?->revoke();

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $identity),
        ]);
    }

    private function setDefaultIdentity(User $user, UserIdentity $identity): void
    {
        if ($identity->is_default === 1) {
            return;
        }

        $user->identities()
            ->whereKey($identity->id)
            ->update(['is_default' => 1]);

        $user->identities()
            ->whereKeyNot($identity->id)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);

        $identity->forceFill(['is_default' => 1]);
    }

    private function respondWithToken(
        Request $request,
        User $user,
        ?UserIdentity $loginIdentity = null,
        ?string $status = null,
    ): JsonResponse {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $this->createRcToken($user);

        $userIdentity = $loginIdentity ?? $user->defaultIdentity()->first();
        $userIdentity?->load('organization');

        if ($userIdentity) {
            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = UserIdentity::class;
                $token->responsible_id = $userIdentity->id;
                $token->save();
            }
        }

        return $this->success(array_filter([
            'status' => $status,
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $userIdentity),
        ], static fn (mixed $value): bool => $value !== null));
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
            ->with('organization')
            ->withBasicInfoFlags()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->each(static function (UserIdentity $identity): void {
                $identity->append(['has_basic_info']);
            });

        $currentIdentity = null;

        if ($userIdentity instanceof UserIdentity) {
            $currentIdentity = $identities->firstWhere('id', $userIdentity->id)
                ?? $userIdentity->append(['has_basic_info'])->makeVisible(['organization']);
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
            'display_avatar' => $user->display_avatar,
            'last_login_ip' => $user->last_login_ip,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'current_identity' => $currentIdentity,
            'identities' => $identities,
        ];
    }
}
