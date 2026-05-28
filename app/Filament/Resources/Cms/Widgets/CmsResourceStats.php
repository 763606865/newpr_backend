<?php

namespace App\Filament\Resources\Cms\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CmsResourceStats extends StatsOverviewWidget
{
    /** @var class-string<Model> */
    public string $modelClass;

    public ?string $cityColumn = null;

    /**
     * @var array<int, array{label: string, value: int|string, color?: string, column?: string}>
     */
    public array $statusCards = [];

    protected ?string $heading = '数据概览';

    protected function getStats(): array
    {
        $query = $this->query();

        $stats = [
            Stat::make('总数', (string) $query->count())->color('primary'),
            Stat::make('本月新增', (string) $this->query()->whereDate('created_at', '>=', now()->startOfMonth()->toDateString())->count())
                ->color('info'),
        ];

        if (filled($this->cityColumn)) {
            $stats[] = Stat::make('全站数据', (string) $this->query()->whereNull($this->cityColumn)->count())
                ->color('gray');
            $stats[] = Stat::make('分站数据', (string) $this->query()->whereNotNull($this->cityColumn)->count())
                ->color('warning');
        }

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
            throw new \RuntimeException('Invalid model class for CMS stats widget.');
        }

        return $model->newQuery();
    }
}
