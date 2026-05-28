<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait InteractsWithMonthlyModelData
{
    /**
     * @return array<int, string>
     */
    protected function monthLabels(int $months = 12): array
    {
        $labels = [];
        $startMonth = now()->startOfMonth()->subMonths($months - 1);

        for ($offset = 0; $offset < $months; $offset++) {
            $labels[] = $startMonth->copy()->addMonths($offset)->format('Y-m');
        }

        return $labels;
    }

    /**
     * @param  array<int, class-string<Model>>  $modelClasses
     * @return array<int, int>
     */
    protected function monthlySeriesForModels(array $modelClasses, int $months = 12): array
    {
        $startMonth = now()->startOfMonth()->subMonths($months - 1);
        $series = array_fill(0, $months, 0);

        foreach ($modelClasses as $modelClass) {
            $model = app($modelClass);

            if (! $model instanceof Model) {
                throw new RuntimeException('Invalid model class for monthly dashboard chart widget.');
            }

            if (! $this->supportsCreatedAt($model)) {
                continue;
            }

            for ($offset = 0; $offset < $months; $offset++) {
                $month = $startMonth->copy()->addMonths($offset);
                $series[$offset] += $model->newQuery()
                    ->whereBetween($model->getCreatedAtColumn(), [
                        $month->copy()->startOfMonth(),
                        $month->copy()->endOfMonth(),
                    ])
                    ->count();
            }
        }

        return $series;
    }

    /**
     * @param  array<int, class-string<Model>>  $modelClasses
     */
    protected function countForCurrentMonth(array $modelClasses): int
    {
        return $this->countForMonthOffset($modelClasses, 0);
    }

    /**
     * @param  array<int, class-string<Model>>  $modelClasses
     */
    protected function countForPreviousMonth(array $modelClasses): int
    {
        return $this->countForMonthOffset($modelClasses, 1);
    }

    /**
     * @param  array<int, class-string<Model>>  $modelClasses
     */
    private function countForMonthOffset(array $modelClasses, int $offsetFromCurrent): int
    {
        $month = now()->startOfMonth()->subMonths($offsetFromCurrent);
        $total = 0;

        foreach ($modelClasses as $modelClass) {
            $model = app($modelClass);

            if (! $model instanceof Model) {
                throw new RuntimeException('Invalid model class for monthly dashboard chart widget.');
            }

            if (! $this->supportsCreatedAt($model)) {
                continue;
            }

            $total += $model->newQuery()
                ->whereBetween($model->getCreatedAtColumn(), [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])
                ->count();
        }

        return $total;
    }

    private function supportsCreatedAt(Model $model): bool
    {
        if (! $model->usesTimestamps()) {
            return false;
        }

        $table = $model->getTable();
        $createdAtColumn = $model->getCreatedAtColumn();

        if ($createdAtColumn === null) {
            return false;
        }

        return Schema::hasColumn($table, $createdAtColumn);
    }
}
