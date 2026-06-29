<?php

namespace App\Filament\Resources\Rc\Announcements\Concerns;

use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Models\Rc\Announcement;

trait InteractsWithAnnouncementRelationsForm
{
    /**
     * @var array<int, string>|null
     */
    protected ?array $pendingCityCodes = null;

    /**
     * @var array<int, string>|null
     */
    protected ?array $pendingMajorCodes = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    protected function extractCityCodesFromFormData(array $data): array
    {
        $cityCodes = $data['city_codes'] ?? [];

        if (! is_array($cityCodes)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $cityCode): string => (string) $cityCode, $cityCodes),
            static fn (string $cityCode): bool => filled($cityCode),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    protected function extractMajorCodesFromFormData(array $data): array
    {
        $majorCodes = $data['major_codes'] ?? [];

        if (! is_array($majorCodes)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $majorCode): string => (string) $majorCode, $majorCodes),
            static fn (string $majorCode): bool => filled($majorCode),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function removeRelationFieldsFromFormData(array $data): array
    {
        unset($data['city_codes'], $data['major_codes']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeAnnouncementRelationsIntoFormData(array $data, Announcement $announcement): array
    {
        $data['city_codes'] = $announcement->cities()
            ->pluck('city_code')
            ->values()
            ->all();

        $data['major_codes'] = $announcement->majors()
            ->pluck('major_code')
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeAnnouncementFormData(array $data): array
    {
        $deadlineType = $data['apply_deadline_type'] ?? null;
        $isUntilFilled = $deadlineType instanceof RcAnnouncementApplyDeadlineType
            ? $deadlineType === RcAnnouncementApplyDeadlineType::UntilFilled
            : (int) $deadlineType === RcAnnouncementApplyDeadlineType::UntilFilled->value;

        if ($isUntilFilled) {
            $data['apply_end_at'] = null;
        }

        if ((bool) ($data['is_nationwide'] ?? false)) {
            $this->pendingCityCodes = [];
        }

        return $data;
    }

    protected function syncAnnouncementRelations(Announcement $announcement): void
    {
        if ($this->pendingCityCodes !== null) {
            $announcement->syncCityCodes($this->pendingCityCodes);
        }

        if ($this->pendingMajorCodes !== null) {
            $announcement->syncMajorCodes($this->pendingMajorCodes);
        }
    }
}
