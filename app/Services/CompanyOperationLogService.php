<?php

namespace App\Services;

use App\Enums\CompanyOperationAction;
use App\Enums\CompanyStatus;
use App\Models\AdminUser;
use App\Models\BUser;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyOperationLogService extends Service
{
    public const LOGS_PER_PAGE = 20;

    /**
     * @var list<string>
     */
    private const COMPANY_LOG_ATTRIBUTES = [
        'name',
        'credit_code',
        'legal_person',
        'contact_phone',
        'address',
        'status',
    ];

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

    public function recordCreated(Company $company): CompanyOperationLog
    {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::Created,
            changes: $this->buildChanges([], $this->snapshotCompanyAttributes($company)),
        );
    }

    public function recordCreatedFromRequest(Company $company, Request $request): CompanyOperationLog
    {
        return $this->recordFromRequest(
            company: $company,
            action: CompanyOperationAction::Created,
            request: $request,
            changes: $this->buildChanges([], $this->snapshotCompanyAttributes($company)),
        );
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function recordCompanyAttributesChanged(Company $company, array $before, ?Request $request = null): void
    {
        $company = $company->fresh();

        if (! $company instanceof Company) {
            return;
        }

        $after = $this->snapshotCompanyAttributes($company);
        $beforeStatus = CompanyStatus::tryFrom((int) ($before['status'] ?? -1));
        $afterStatus = $company->status;

        if ($beforeStatus instanceof CompanyStatus
            && $afterStatus instanceof CompanyStatus
            && $beforeStatus !== $afterStatus) {
            $statusChanges = $this->buildChanges(
                $this->formatStatusSnapshot($beforeStatus),
                $this->formatStatusSnapshot($afterStatus),
            );

            if ($request instanceof Request) {
                $this->recordFromRequest(
                    company: $company,
                    action: CompanyOperationAction::StatusChanged,
                    request: $request,
                    changes: $statusChanges,
                );
            } else {
                $this->record(
                    company: $company,
                    action: CompanyOperationAction::StatusChanged,
                    changes: $statusChanges,
                );
            }
        }

        $changes = $this->buildChangedAttributes($before, $after, ['status', 'status_label']);

        if ($changes === null) {
            return;
        }

        if ($request instanceof Request) {
            $this->recordFromRequest(
                company: $company,
                action: CompanyOperationAction::Updated,
                request: $request,
                changes: $changes,
            );
        } else {
            $this->record(
                company: $company,
                action: CompanyOperationAction::Updated,
                changes: $changes,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function recordUpdated(Company $company, array $before, array $after): ?CompanyOperationLog
    {
        $changes = $this->buildChangedAttributes($before, $after, ['status', 'status_label']);

        if ($changes === null) {
            return null;
        }

        return $this->record(
            company: $company,
            action: CompanyOperationAction::Updated,
            changes: $changes,
        );
    }

    public function recordDeleted(Company $company): CompanyOperationLog
    {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::Deleted,
            changes: $this->buildChanges(
                $this->snapshotCompanyAttributes($company),
                ['deleted_at' => now()->toDateTimeString()],
            ),
        );
    }

    public function recordStatusChanged(
        Company $company,
        CompanyStatus $before,
        CompanyStatus $after,
    ): CompanyOperationLog {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::StatusChanged,
            changes: $this->buildChanges(
                $this->formatStatusSnapshot($before),
                $this->formatStatusSnapshot($after),
            ),
        );
    }

    public function recordAuditApproved(Company $company, CompanyStatus $before): CompanyOperationLog
    {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::AuditApproved,
            changes: $this->buildChanges(
                $this->formatStatusSnapshot($before),
                $this->formatStatusSnapshot(CompanyStatus::Enabled),
            ),
            extra: [
                'auditor_id' => $company->auditor_id,
            ],
        );
    }

    public function recordAuditRejected(Company $company, CompanyStatus $before): CompanyOperationLog
    {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::AuditRejected,
            changes: $this->buildChanges(
                $this->formatStatusSnapshot($before),
                $this->formatStatusSnapshot(CompanyStatus::Disabled),
            ),
            extra: [
                'auditor_id' => $company->auditor_id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $beforePlan
     * @param  array<string, mixed>  $afterPlan
     * @param  array<string, mixed>  $ship
     */
    public function recordPlanBound(
        Company $company,
        ?array $beforePlan,
        array $afterPlan,
        array $ship = [],
    ): CompanyOperationLog {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::PlanBound,
            changes: $this->buildChanges($beforePlan ?? [], $afterPlan),
            extra: filled($ship) ? ['ship' => $ship] : null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $beforePlan
     * @param  array<string, mixed>  $afterPlan
     */
    public function recordPlanRefreshed(
        Company $company,
        ?array $beforePlan,
        array $afterPlan,
        ?string $remark = null,
    ): CompanyOperationLog {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::PlanRefreshed,
            changes: $this->buildChanges($beforePlan ?? [], $afterPlan),
            extra: filled($remark) ? ['remark' => $remark] : null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $beforePlan
     * @param  array<string, mixed>  $afterPlan
     */
    public function recordPlanBatchRebound(
        Company $company,
        ?array $beforePlan,
        array $afterPlan,
        int $planId,
        ?Model $operator = null,
    ): CompanyOperationLog {
        return $this->record(
            company: $company,
            action: CompanyOperationAction::PlanBatchRebound,
            changes: $this->buildChanges($beforePlan ?? [], $afterPlan),
            extra: ['plan_id' => $planId],
            operator: $operator,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotCompanyAttributes(Company $company): array
    {
        $snapshot = [];

        foreach (self::COMPANY_LOG_ATTRIBUTES as $attribute) {
            $value = $company->getAttribute($attribute);

            if ($attribute === 'status') {
                $status = $this->resolveCompanyStatus($value);
                $snapshot['status'] = $status?->value;
                $snapshot['status_label'] = $status?->getLabel();

                continue;
            }

            $snapshot[$attribute] = $value;
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function snapshotCurrentPlan(Company $company): ?array
    {
        $currentPlan = $company->companyPlans()
            ->where('is_current', 1)
            ->with('ship')
            ->first();

        if ($currentPlan === null) {
            return null;
        }

        return [
            'plan_id' => $currentPlan->plan_id,
            'plan_name' => $currentPlan->ship?->plan_name,
            'plan_code' => $currentPlan->ship?->plan_code,
            'ship_id' => $currentPlan->ship_id,
            'pay_amount' => $currentPlan->ship?->pay_amount,
        ];
    }

    /**
     * @param  array{from?: string|null, until?: string|null, action?: string|null, operator?: string|null}  $filters
     */
    public function queryForCompany(Company $company, array $filters = []): Builder
    {
        $query = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->with('operator')
            ->latest('created_at');

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (filled($filters['until'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        if (filled($filters['action'] ?? null)) {
            $query->where('action', $filters['action']);
        }

        if (filled($filters['operator'] ?? null)) {
            if ($filters['operator'] === 'system') {
                $query->where(function (Builder $operatorQuery): void {
                    $operatorQuery
                        ->whereNull('operator_id')
                        ->orWhereNull('operator_type');
                });
            } elseif (is_string($filters['operator']) && str_contains($filters['operator'], ':')) {
                [$operatorType, $operatorId] = explode(':', $filters['operator'], 2);

                $query
                    ->where('operator_type', $operatorType)
                    ->where('operator_id', (int) $operatorId);
            }
        }

        return $query;
    }

    /**
     * @param  array{from?: string|null, until?: string|null, action?: string|null, operator?: string|null}  $filters
     */
    public function paginateForCompany(
        Company $company,
        array $filters = [],
        int $page = 1,
        int $perPage = self::LOGS_PER_PAGE,
    ): LengthAwarePaginator {
        return $this->queryForCompany($company, $filters)
            ->paginate(
                perPage: $perPage,
                page: max(1, $page),
            );
    }

    /**
     * @return array<string, string>
     */
    public function operatorFilterOptions(Company $company): array
    {
        $options = ['' => '全部'];

        $hasSystemLogs = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('operator_id')
                    ->orWhereNull('operator_type');
            })
            ->exists();

        if ($hasSystemLogs) {
            $options['system'] = '系统';
        }

        $logs = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->whereNotNull('operator_id')
            ->whereNotNull('operator_type')
            ->with('operator')
            ->orderByDesc('created_at')
            ->get();

        foreach ($logs as $log) {
            $key = $log->operator_type.':'.$log->operator_id;

            if (isset($options[$key])) {
                continue;
            }

            $options[$key] = $this->formatOperatorLabel($log);
        }

        return $options;
    }

    public function formatOperatorLabel(CompanyOperationLog $log): string
    {
        if ($log->isSystemAction()) {
            return '系统';
        }

        $name = $log->operator?->name;

        return filled($name) ? (string) $name : '未知';
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $except
     * @return array{before: array<string, mixed>, after: array<string, mixed>}|null
     */
    public function buildChangedAttributes(array $before, array $after, array $except = []): ?array
    {
        $changedBefore = [];
        $changedAfter = [];

        foreach ($after as $key => $value) {
            if (in_array($key, $except, true)) {
                continue;
            }

            if (($before[$key] ?? null) !== $value) {
                $changedBefore[$key] = $before[$key] ?? null;
                $changedAfter[$key] = $value;
            }
        }

        if ($changedBefore === []) {
            return null;
        }

        return $this->buildChanges($changedBefore, $changedAfter);
    }

    /**
     * @return array{status: int|null, status_label: string|null}
     */
    private function formatStatusSnapshot(CompanyStatus $status): array
    {
        return [
            'status' => $status->value,
            'status_label' => $status->getLabel(),
        ];
    }

    private function resolveCompanyStatus(mixed $value): ?CompanyStatus
    {
        if ($value instanceof CompanyStatus) {
            return $value;
        }

        return CompanyStatus::tryFrom((int) $value);
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
