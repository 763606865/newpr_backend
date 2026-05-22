<?php

namespace App\Services;

use App\Enums\CompanyPlanStatus;
use App\Enums\CompanyStatus;
use App\Exceptions\BadRequestException;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client as PassportClient;

class CompanyService extends Service
{
    public function create(array $params = []): Company
    {
        $params['status'] = $this->getGuardName() === 'admin' ? CompanyStatus::Enabled : CompanyStatus::Auditing;

        return Company::query()->firstOrCreate([
            'name' => $params['name'],
            'credit_code' => $params['credit_code'] ?? null,
        ], $params);
    }

    public function getCurrentBizPlan(Company $company): array
    {
        return $this->getCurrentBizPlanData($company)->toArray();
    }

    public function getCurrentBizPlanData(Company $company): CurrentBizPlanData
    {
        $client = $this->resolveCurrentClient();

        if (! $client) {
            throw new BadRequestException('Client not set or not authenticated.');
        }

        $currentCompanyPlan = $company->companyPlans()
            ->with(['ship'])
            ->where('is_current', 1)
            ->where('status', CompanyPlanStatus::Enabled->value)
            ->latest('id')
            ->first();

        $shipCompanyPlan = $currentCompanyPlan?->ship;

        if (! $shipCompanyPlan) {
            return CurrentBizPlanData::empty();
        }

        return new CurrentBizPlanData(
            id: $shipCompanyPlan->plan_id,
            name: $shipCompanyPlan->plan_name,
            code: $shipCompanyPlan->plan_code,
            startTime: $shipCompanyPlan->start_time?->toDateTimeString(),
            endTime: $shipCompanyPlan->end_time?->toDateTimeString(),
            menus: $this->normalizeMenus($shipCompanyPlan->menus ?? [], $client),
            features: $this->normalizeFeatures($shipCompanyPlan->features ?? [], $client),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMenus(array $menus, ?PassportClient $client): array
    {
        if (! $client) {
            return [];
        }

        $normalized = [];

        $menus = array_values(array_filter(
            $menus,
            fn (mixed $item): bool => $this->belongsToClient($item, $client),
        ));

        foreach ($menus as $menu) {
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
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFeatures(array $features, ?PassportClient $client): array
    {
        if (! $client) {
            return [];
        }

        $normalized = [];

        $features = array_values(array_filter(
            $features,
            fn (mixed $feature): bool => $this->belongsToClient($feature, $client),
        ));

        foreach ($features as $feature) {

            $normalized[] = [
                'id' => $feature['id'] ?? null,
                'client_id' => $feature['client_id'] ?? null,
                'name' => $feature['feature_name'] ?? null,
                'code' => $feature['feature_code'] ?? null,
            ];
        }

        return $normalized;
    }

    private function belongsToClient(mixed $item, PassportClient $client): bool
    {
        if (! is_array($item)) {
            return false;
        }

        if (! isset($item['client_id']) || blank($item['client_id'])) {
            return false;
        }

        return (string) $item['client_id'] === (string) $client->id;
    }

    private function resolveCurrentClient(): ?PassportClient
    {
        $guardName = $this->resolveCurrentGuardName();
        $provider = (string) config("auth.guards.{$guardName}.provider", '');

        if ($provider === '') {
            return null;
        }

        return PassportClient::query()
            ->where('provider', $provider)
            ->first();
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
