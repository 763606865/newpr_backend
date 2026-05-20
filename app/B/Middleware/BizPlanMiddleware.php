<?php

namespace App\B\Middleware;

use App\Models\BUser;
use App\Models\Company;
use App\Services\CompanyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BizPlanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permissionCode = null): Response
    {
        /** @var BUser|null $user */
        $user = $request->user('b');
        if (! $user) {
            return $this->forbidden('请先登录后访问。', Response::HTTP_UNAUTHORIZED);
        }

        $company = $this->resolveCompany($request);
        if (! $company) {
            return $this->forbidden('当前 Token 未绑定企业上下文。');
        }

        $plan = CompanyService::make()->getCurrentBizPlan($company);
        if (! $plan) {
            return $this->forbidden('当前企业未开通可用套餐。');
        }

        if ($this->isAllowed($request, $plan, $permissionCode)) {
            return $next($request);
        }

        return $this->forbidden('当前套餐不支持访问该接口。');
    }

    private function resolveCompany(Request $request): ?Company
    {
        $token = $request->user('b')?->token();

        $company = $token?->responsible;

        return $company instanceof Company ? $company : null;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function isAllowed(Request $request, array $plan, ?string $permissionCode): bool
    {
        $menuCodes = collect($plan['menus'] ?? [])->pluck('menu_code')->filter()->map(static fn (mixed $code): string => (string) $code)->all();
        $featureCodes = collect($plan['features'] ?? [])->pluck('code')->filter()->map(static fn (mixed $code): string => (string) $code)->all();
        $menuPaths = collect($plan['menus'] ?? [])->pluck('path')->filter()->map(static fn (mixed $path): string => (string) $path)->all();

        $candidateCodes = [];
        if ($permissionCode) {
            $candidateCodes = array_filter(array_map('trim', explode(',', $permissionCode)));
        } else {
            $routeName = $request->route()?->getName();
            if (is_string($routeName) && $routeName !== '') {
                $candidateCodes[] = $routeName;
            }
        }

        foreach ($candidateCodes as $code) {
            if (in_array($code, $menuCodes, true) || in_array($code, $featureCodes, true)) {
                return true;
            }
        }

        $requestPath = $this->normalizePath('/'.$request->path());
        $requestPathWithoutB = $this->normalizePath('/'.Str::after($request->path(), 'b/'));

        foreach ($menuPaths as $menuPath) {
            $normalizedMenuPath = $this->normalizePath($menuPath);
            if ($normalizedMenuPath === '') {
                continue;
            }

            if ($requestPath === $normalizedMenuPath || Str::startsWith($requestPath, $normalizedMenuPath.'/')) {
                return true;
            }

            if ($requestPathWithoutB === $normalizedMenuPath || Str::startsWith($requestPathWithoutB, $normalizedMenuPath.'/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = '/'.ltrim($path, '/');

        return $normalizedPath === '/' ? $normalizedPath : rtrim($normalizedPath, '/');
    }

    private function forbidden(string $message, int $status = Response::HTTP_FORBIDDEN): Response
    {
        $now = microtime(true);

        return response()->json([
            'code' => $status,
            'message' => $message,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - LARAVEL_START,
            ],
        ], $status);
    }
}
