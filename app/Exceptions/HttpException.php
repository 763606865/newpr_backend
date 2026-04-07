<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Throwable;

abstract class HttpException extends Exception
{
    public function report(): void
    {
        //
    }

    public function render(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $response = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];
        if (config('app.debug')) {
            $response['trace'] = $this->getTrace();
        }
        try {
            return response()->json($response);
        } catch (Exception $e) {
            return null;
        }
    }
}
