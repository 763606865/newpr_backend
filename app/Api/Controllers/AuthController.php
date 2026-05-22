<?php

namespace App\Api\Controllers;

use App\Api\Requests\EmailLoginRequest;
use App\Api\Requests\ForgotPasswordRequest;
use App\Api\Requests\PhoneLoginRequest;
use App\Api\Requests\SendVerificationCodeRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\UserService;
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
     * POST /api/auth/phone-login
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
     * POST /api/auth/email-login
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
     * GET /api/auth/me
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
     * POST /api/auth/logout
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
     * POST /api/auth/refresh-token
     *
     * @throws \Exception
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        $employee = $user->token()?->responsible;

        if ($request->filled('employee_id')) {
            $employeeId = (int) $request->input('employee_id');

            if ($employeeId <= 0) {
                return $this->error('employee_id 参数无效。', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $employee = $user->employees()->active()->whereKey($employeeId)->first();

            if (! $employee) {
                return $this->error('无权切换到该企业。', Response::HTTP_FORBIDDEN);
            }
        }

        $tokenResult = $user->createToken('api');

        if ($employee) {
            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = Employee::class;
                $token->responsible_id = $employee->id;
                $token->save();
            }
        }

        $user->token()?->revoke();

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $employee),
        ]);
    }

    private function respondWithToken(Request $request, User $user): JsonResponse
    {
        $user->forceFill([
            'last_login_ip' => (string) $request->ip(),
            'last_login_at' => now(),
        ])->save();

        $tokenResult = $user->createToken('api');

        $employee = $user->employees()->active()->with('company')->orderByDesc('entry_time')->first();

        if ($employee) {
            if ($token = $tokenResult->getToken()) {
                $token->responsible_type = Employee::class;
                $token->responsible_id = $employee->id;
                $token->save();
            }
        }

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'user' => $this->userPayload($user, $employee),
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
    private function userPayload(User $user, ?Employee $employee): array
    {
        $employees = $user->employees()->active()->with([
            'company:id,name',
            'department:id,name',
            'position:id,name',
        ])->orderByDesc('entry_time')->get()->setVisible([
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
            'current_employee' => $this->employeePayload($employee),
            'employees' => $employees,
        ];
    }

    private function employeePayload(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }
        $company = $employee->company;
        /** @var CompanyService $service */
        $service = CompanyService::make();
        $planData = $service->getCurrentBizPlanData($company);

        return [
            'id' => $employee->id,
            'company' => $company->setVisible(['id', 'name']),
            'employee_no' => $employee->employee_no,
            'real_name' => $employee->real_name,
            'avatar' => $employee->avatar,
            'email' => $employee->email,
            'mobile' => $employee->mobile,
            'entry_time' => $employee->entry_time,
            'menus' => $planData->menus,
            'plan' => $planData->planPayload(),
            'features' => $planData->features,
        ];
    }
}
