<?php

namespace App\Services;

use App\Enums\CompanyOperationAction;
use App\Models\AdminUser;
use App\Models\BUser;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyOperationLogService extends Service
{
    /**
     * @param  array{before?: array<string, mixed>, after?: array<string, mixed>}|null  $changes
     * @param  array<string, mixed>|null  $extra
     */
    public function record(
        Company $company,
        CompanyOperationAction $action,
        ?string $summary = null,
        ?array $changes = null,
        ?Model $operator = null,
        ?array $extra = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): CompanyOperationLog {
        $operator = $this->resolveOperator($operator);

        return CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'operator_id' => $operator?->getKey(),
            'operator_type' => $operator?->getMorphClass(),
            'action' => $action,
            'summary' => $summary ?? $action->getLabel(),
            'changes' => $changes,
            'ip' => $this->resolveIp($ip),
            'user_agent' => $this->resolveUserAgent($userAgent),
            'extra' => $extra,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function buildChanges(array $before, array $after): array
    {
        return [
            'before' => $before,
            'after' => $after,
        ];
    }

    /**
     * @param  array{before?: array<string, mixed>, after?: array<string, mixed>}|null  $changes
     * @param  array<string, mixed>|null  $extra
     */
    public function recordFromRequest(
        Company $company,
        CompanyOperationAction $action,
        Request $request,
        ?string $summary = null,
        ?array $changes = null,
        ?Model $operator = null,
        ?array $extra = null,
    ): CompanyOperationLog {
        return $this->record(
            company: $company,
            action: $action,
            summary: $summary,
            changes: $changes,
            operator: $operator,
            extra: $extra,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    private function resolveOperator(?Model $operator): ?Model
    {
        if ($operator instanceof Model) {
            return $operator;
        }

        foreach (['admin', 'b'] as $guard) {
            $user = Auth::guard($guard)->user();

            if ($user instanceof AdminUser || $user instanceof BUser) {
                return $user;
            }
        }

        return null;
    }

    private function resolveIp(?string $ip): ?string
    {
        if (filled($ip)) {
            return $ip;
        }

        return request()->ip();
    }

    private function resolveUserAgent(?string $userAgent): ?string
    {
        if (filled($userAgent)) {
            return $userAgent;
        }

        $agent = request()->userAgent();

        return filled($agent) ? $agent : null;
    }
}
