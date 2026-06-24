<?php

namespace App\Http\Middleware;

use App\Services\CmsMenuAudienceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsHomeMenuAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $menuCode = null): Response
    {
        CmsMenuAudienceService::make()->assertRouteAccessible(
            $request,
            $menuCode ?? (string) $request->route()?->getName(),
        );

        return $next($request);
    }
}
