<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class HttpException extends Exception
{
    public function report(): void
    {
        //
    }

    public function render(Request $request): ?JsonResponse
    {
        $response = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];
        if (config('app.debug')) {
            $response['trace'] = $this->getTrace();
        }
        try {
            return response()->json($response, $this->getCode());
        } catch (Exception $e) {
            return null;
        }
    }
}
