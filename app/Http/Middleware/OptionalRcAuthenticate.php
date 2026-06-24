<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalRcAuthenticate
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && ! $request->user('rc')) {
            try {
                Auth::shouldUse('rc');
                Auth::guard('rc')->authenticate();
            } catch (AuthenticationException) {
                // 无效 token 时按游客继续访问 CMS。
            }
        }

        return $next($request);
    }
}
