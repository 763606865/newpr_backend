<?php

namespace App\Services;

use App\Enums\CompanyPlanStatus;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client as PassportClient;

class CompanyService extends Service
{
    public function create(array $params = [])
    {
        $params['status'] = $this->getGuardName() === 'admin' ? CompanyStatus::Enabled : CompanyStatus::Auditing;

        return Company::query()->firstOrCreate([
            'name' => $params['name'],
            'credit_code' => $params['credit_code'] ?? null,
        ], $params);
    }

    public function getCurrentBizPlan(Company $company): array
    {
        $clientIds = $this->resolveCurrentClientIds();

        $currentCompanyPlan = $company->companyPlans()
            ->with(['ship'])
            ->where('is_current', 1)
            ->where('status', CompanyPlanStatus::Enabled->value)
            ->latest('id')
            ->first();

        $shipCompanyPlan = $currentCompanyPlan?->ship;

        $plan = null;
        if ($shipCompanyPlan) {
            $plan = [
                'id' => $shipCompanyPlan->plan_id,
                'name' => $shipCompanyPlan->plan_name,
                'code' => $shipCompanyPlan->plan_code,
                'start_time' => $shipCompanyPlan->start_time?->toDateTimeString(),
                'end_time' => $shipCompanyPlan->end_time?->toDateTimeString(),
                'menus' => $this->normalizeMenus($shipCompanyPlan?->menus ?? [], $clientIds),
                'features' => $this->normalizeFeatures($shipCompanyPlan?->features ?? [], $clientIds),
            ];
        }

        return $plan ?? [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @param  array<int, string>  $clientIds
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMenus(array $menus, array $clientIds = []): array
    {
        $normalized = [];

        foreach ($menus as $menu) {
            if (! empty($clientIds) && ! in_array((string) ($menu['client_id'] ?? ''), $clientIds, true)) {
                continue;
            }

            $normalized[] = [
                'id' => $menu['id'] ?? null,
                'client_id' => $menu['client_id'] ?? null,
                'parent_id' => $menu['parent_id'] ?? null,
                'menu_name' => $menu['menu_name'] ?? null,
                'menu_code' => $menu['menu_code'] ?? null,
                'menu_type' => $menu['menu_type'] ?? null,
                'path' => $menu['path'] ?? null,
                'component' => $menu['component'] ?? null,
                'icon' => $menu['icon'] ?? null,
                'sort' => $menu['sort'] ?? null,
                'visible' => $menu['visible'] ?? null,
                'style' => $menu['style'] ?? null,
                'extra' => $menu['extra'] ?? null,
            ];
        }

        $sortValues = array_column($normalized, 'sort');
        array_multisort($sortValues, SORT_ASC, $normalized);

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $features
     * @param  array<int, string>  $clientIds
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFeatures(array $features, array $clientIds = []): array
    {
        $normalized = [];

        foreach ($features as $feature) {
            if (! empty($clientIds) && ! in_array((string) ($feature['client_id'] ?? ''), $clientIds, true)) {
                continue;
            }

            $normalized[] = [
                'id' => $feature['id'] ?? null,
                'client_id' => $feature['client_id'] ?? null,
                'name' => $feature['feature_name'] ?? null,
                'code' => $feature['feature_code'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function resolveCurrentClientIds(): array
    {
        $guardName = $this->resolveCurrentGuardName();
        $provider = (string) config("auth.guards.{$guardName}.provider", '');

        if ($provider === '') {
            return [];
        }

        return PassportClient::query()
            ->where('provider', $provider)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    private function resolveCurrentGuardName(): string
    {
        foreach (array_keys((array) config('auth.guards', [])) as $guardName) {
            if (Auth::guard($guardName)->check()) {
                return (string) $guardName;
            }
        }

        return $this->getGuardName();
    }
}
