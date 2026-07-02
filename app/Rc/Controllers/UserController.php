<?php

namespace App\Rc\Controllers;

use App\Models\User;
use App\Rc\Requests\UserPhoneLookupRequest;
use App\Rc\Requests\UserPhoneUpdateRequest;
use App\Rc\Requests\UserPhoneVerificationCodeRequest;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
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

    private function phoneBelongsToAnotherUser(string $phone, User $user): bool
    {
        return User::query()
            ->where('phone', $phone)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
