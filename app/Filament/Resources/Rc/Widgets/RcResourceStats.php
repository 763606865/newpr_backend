<?php

namespace App\Filament\Resources\Rc\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RcResourceStats extends StatsOverviewWidget
{
    /** @var class-string<Model> */
    public string $modelClass;

    public string $todayColumn = 'created_at';

    public string $todayLabel = '今日新增';

    /**
     * @var array<int, array{label: string, value: int|string, color?: string, column?: string}>
     */
    public array $statusCards = [];

    protected ?string $heading = '运营概览';

    protected function getStats(): array
    {
        $stats = [
            Stat::make('总数', (string) $this->query()->count())->color('primary'),
            Stat::make($this->todayLabel, (string) $this->query()->whereDate($this->todayColumn, '=', now()->toDateString())->count())
                ->color('success'),
            Stat::make('本月新增', (string) $this->query()->whereDate($this->todayColumn, '>=', now()->startOfMonth()->toDateString())->count())
                ->color('info'),
        ];

        foreach ($this->statusCards as $card) {
            $column = $card['column'] ?? 'status';
            $stats[] = Stat::make(
                $card['label'],
                (string) $this->query()->where($column, '=', $card['value'])->count(),
            )->color($card['color'] ?? 'gray');
        }

        return $stats;
    }

    private function query(): Builder
    {
        $model = app($this->modelClass);

        if (! $model instanceof Model) {
            throw new \RuntimeException('Invalid model class for RC stats widget.');
        }

        return $model->newQuery();
    }
}
