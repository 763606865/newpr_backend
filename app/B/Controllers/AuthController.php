<?php

namespace App\B\Controllers;

use App\B\Requests\LoginRequest;
use App\B\Requests\SendVerificationCodeRequest;
use App\Exceptions\BadRequestException;
use App\Models\BUser;
use App\Models\Company;
use App\Services\BUserService;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * 登录B端用户
     *
     * POST /b/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = (string) $validated['phone'];

        if (! VerificationCodeService::make()->verify('phone', $phone, 'b-login', (string) $validated['code'])) {
            return $this->error('验证码错误或已失效。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = BUser::query()->where('phone', $phone)->first();

        if (! $user) {
            $user = BUserService::make()->register([
                'phone' => $phone,
            ]);
        }

        return $this->respondWithToken($request, $user);
    }

    /**
     * 登出
     *
     * POST /b/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * 获取用户信息
     *
     * GET /b/auth/me
     *
     * @throws BadRequestException
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success($this->userPayload($user, $this->company()));
    }

    /**
     * 发送短信验证码
     *
     * POST /b/auth/send-verification-code
     *
     * @throws \Exception
     */
    public function sendVerificationCode(SendVerificationCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = VerificationCodeService::make()->send('phone', $validated['phone'], 'b-login');

        if (! $result['sent']) {
            return $this->error((string) $result['message'], Response::HTTP_TOO_MANY_REQUESTS, [
                'expires_in' => $result['expires_in'],
            ]);
        }

        return $this->success();
    }

    /**
     * 刷新token
     *
     * POST /b/auth/refresh-token
     *
     * @throws \Exception
     */
    public function refreshToken(Request $request): JsonResponse
    {
        /** @var BUser|null $user */
        $user = $this->user();

        $company = $this->company();

        if ($request->filled('company_id')) {
            $companyId = (int) $request->input('company_id');

            if ($companyId <= 0) {
                return $this->error('company_id 参数无效。', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $company = $user->companies()->whereKey($companyId)->first();

            if (! $company) {
                return $this->error('无权切换到该企业。', Response::HTTP_FORBIDDEN);
            }
        }

        $tokenResult = $user->createToken('b');

        if ($company) {
            $company->pivot->last_login_ip = (string) $request->ip();
            $company->pivot->last_login_at = now();
            $company->pivot->save();

            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = Company::class;
                $token->responsible_id = $company->id;
                $token->save();
            }
        }

        $user->token()?->revoke();

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $company),
        ]);
    }

    private function respondWithToken(Request $request, BUser $user): JsonResponse
    {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $user->createToken('b');

        $company = $user->companies()
            ->orderBy('status', 'ASC')
            ->first();

        if ($company) {
            $company->pivot->last_login_ip = (string) $request->ip();
            $company->pivot->last_login_at = now();
            $company->pivot->save();
            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = Company::class;
                $token->responsible_id = $company->id;
                $token->save();
            }
        }

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $company),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(BUser $user, ?Company $company): array
    {
        $companies = $user->companies()->get();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'last_login_ip' => $user->last_login_ip,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'current_company' => $company ?? null,
            'companies' => $companies,
        ];
    }
}
