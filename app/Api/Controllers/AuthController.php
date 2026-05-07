<?php

namespace App\Api\Controllers;

use App\Http\Requests\Api\EmailLoginRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\PhoneLoginRequest;
use App\Http\Requests\Api\SendVerificationCodeRequest;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * 发送验证码接口
     *
     * POST /api/auth/send-verification-code
     *
     * @param SendVerificationCodeRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function sendVerificationCode(SendVerificationCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = (string) $validated['type'];
        $account = (string) $validated['account'];
        $scene = (string) $validated['scene'];

        $user = $this->findUserByType($type, $account);

        if ($scene === 'login' && ! $user) {
            return $this->error('用户不存在。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($scene === 'forgot_password' && ! $user) {
            return $this->error('用户不存在，无法重置密码。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = VerificationCodeService::make()->send($type, $account, $scene);

        if (! $result['sent']) {
            return $this->error((string) $result['message'], Response::HTTP_TOO_MANY_REQUESTS, [
                'expires_in' => $result['expires_in'],
            ]);
        }

        return $this->success([
            'expires_in' => $result['expires_in'],
            'code' => $result['code'],
        ]);
    }

    /**
     * 手机号登录接口
     *
     * POST /api/auth/phone-login
     *
     * @param PhoneLoginRequest $request
     * @return JsonResponse
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
            return $this->error('用户不存在。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->respondWithToken($request, $user);
    }

    /**
     * 邮箱登录接口
     *
     * POST /api/auth/email-login
     *
     * @param EmailLoginRequest $request
     * @return JsonResponse
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
            return $this->error('用户不存在。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->respondWithToken($request, $user);
    }

    /**
     * 用户信息
     *
     * GET /api/auth/me
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * 退出登录
     *
     * POST /api/auth/logout
     *
     * @param Request $request
     * @return JsonResponse
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

    private function respondWithToken(Request $request, User $user): JsonResponse
    {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $user->createToken('api');

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user),
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
    private function userPayload(User $user): array
    {
        $employees = $user->employees()->active()->with([
            'company:id,name',
            'department:id,name',
            'position:id,name',
        ])->get()->setVisible([
            'id',
            'employee_no',
            'real_name',
            'avatar',
            'email',
            'mobile',
            'entry_time',
        ])->makeVisible([
            'company',
            'department',
            'position',
        ]);
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
            'employees' => $employees,
        ];
    }
}
