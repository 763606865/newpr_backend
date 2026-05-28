<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class DomainStatsWidget extends StatsOverviewWidget
{
    /** @var array<int, array{label: string, modelClass: class-string<Model>, color?: string}> */
    public array $cards = [];

    public ?string $heading = '数据概览';

    protected int|array|null $columns = null;

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getColumns(): int|array|null
    {
        return $this->columns;
    }

    protected function getStats(): array
    {
        $stats = [];

        foreach ($this->cards as $card) {
            $stats[] = Stat::make(
                $card['label'],
                (string) $this->count($card['modelClass']),
            )->color($card['color'] ?? 'primary');
        }

        return $stats;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function count(string $modelClass): int
    {
        $model = app($modelClass);

        if (! $model instanceof Model) {
            throw new RuntimeException('Invalid model class for dashboard stats widget.');
        }

        return $model->newQuery()->count();
    }
}
