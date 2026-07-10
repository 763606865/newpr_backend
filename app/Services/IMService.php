<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Libs\Facades\Im;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;

class IMService extends Service
{
    public function createOrUpdate(UserIdentity $identity)
    {
        $user = $identity->user;
        $params = [
            'external_user_id' => $identity->external_user_id,
            'nickname' => $user->nickname,
            'avatar_url' => $user->display_avatar
        ];
        $im = Im::user();
        $imDriver = $im->getDriver();
        $response = $im->createOrUpdateUser($params);
        if (!isset($response['code'], $response['data']) || $response['code'] !== 200) {
            throw new BadRequestException("IM API Error: " . ($response['message'] ?? 'Unknown error'));
        }
        UserIm::query()->updateOrCreate([
            'user_id' => $user->id,
            'user_identity_id' => $identity->id,
            'identity_type' => $identity->identity_type,
            'provider' => $imDriver->getProvider(),
            'app_code' => $imDriver->getAppCode(),
        ], [
            'external_user_id' => $identity->external_user_id,
            'im_user_id' => $response['data']['id'] ?? null,
        ]);
    }
}
