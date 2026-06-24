<?php

namespace App\Filament\Resources\Cms\Menus\Concerns;

use App\Models\Cms\Menu;

trait InteractsWithMenuIdentitiesForm
{
    /**
     * @var array<int, int>|null
     */
    protected ?array $pendingIdentityTypes = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    protected function extractIdentityTypesFromFormData(array $data): array
    {
        $identityTypes = $data['identity_types'] ?? [];

        if (! is_array($identityTypes)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $identityType): int => (int) $identityType,
            $identityTypes,
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function removeIdentityTypesFromFormData(array $data): array
    {
        unset($data['identity_types']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeMenuIdentitiesIntoFormData(array $data, Menu $menu): array
    {
        $data['identity_types'] = $menu->menuIdentities()
            ->pluck('identity_type')
            ->map(static fn (mixed $identityType): int => $identityType instanceof \BackedEnum ? $identityType->value : (int) $identityType)
            ->values()
            ->all();

        return $data;
    }

    protected function syncMenuIdentities(Menu $menu): void
    {
        $identityTypes = $this->pendingIdentityTypes ?? [];

        $menu->menuIdentities()->delete();

        foreach ($identityTypes as $identityType) {
            $menu->menuIdentities()->create([
                'identity_type' => $identityType,
            ]);
        }
    }
}
