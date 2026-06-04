<?php

namespace App\SApi\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * 健康检查（需通过加签鉴权）
     *
     * GET /sapi/ping
     */
    public function ping(Request $request): JsonResponse
    {
        $client = $this->client();

        return $this->success([
            'message' => 'pong',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'app_key' => $client->app_key,
            ],
        ]);
    }
}
