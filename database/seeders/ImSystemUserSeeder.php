<?php

namespace Database\Seeders;

use App\Libs\Facades\Im;
use App\Models\ImSystemUser;
use Illuminate\Database\Seeder;

class ImSystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payload = [
            'external_user_id' => config('app.name').'_system_1',
            'nickname' => config('app.name').'官方',
            'avatar_url' => '',
            'user_type' => 'system',
        ];
        $im = Im::user();
        $imDriver = $im->getDriver();
        $response = $im->createOrUpdateUser($payload);
        ImSystemUser::query()->firstOrCreate([
            'code' => 'system_1',
            'name' => $payload['nickname'],
            'provider' => $imDriver->getProvider(),
            'app_code' => $imDriver->getAppCode(),
            'external_user_id' => $payload['external_user_id'],
        ], [
            'im_user_id' => $response['id'] ?? null,
        ]);
    }
}
