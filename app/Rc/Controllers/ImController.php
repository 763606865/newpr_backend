<?php

namespace App\Rc\Controllers;

use App\Services\IMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImController extends Controller
{
    /**
     * 返回IM的token
     *
     * GET /rc/im/refresh-token
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function refreshToken(Request $request): JsonResponse
    {
        return $this->success(IMService::make()->resolvedToken($this->currentIdentity()));
    }
}
