<?php

namespace App\Filament\Resources\System\Plans\Tables;

use App\Enums\SystemPlanStatus;
use App\Models\Oa\Feature;
use App\Services\PassportClientService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_name')
                    ->label('方案名称')
                    ->searchable(),

                TextColumn::make('plan_code')
                    ->label('方案编码')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('价格')
                    ->money('CNY'),

                TextColumn::make('duration')
                    ->label('时长(天)')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '永久' : $state.'天'),

                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('状态')
                    ->badge(),

                TextColumn::make('features_count')
                    ->label('功能点数')
                    ->counts('features')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(SystemPlanStatus::class),
            ])
            ->recordActions([
                Action::make('bind_features')
                    ->label('绑定功能点')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->modalWidth('4xl')
                    ->fillForm(fn ($record): array => self::resolveFeatureSelectionsByGroup($record))
                    ->schema(fn (): array => self::buildFeatureBindingSchema())
                    ->action(function ($record, array $data): void {
                        $selectedFeatureIds = Feature::query()
                            ->whereIn('id', self::flattenSelectedFeatureIds($data))
                            ->pluck('id')
                            ->map(static fn ($id): int => (int) $id)
                            ->all();

                        $record->features()->sync($selectedFeatureIds);
                    })
                    ->successNotificationTitle('功能点绑定成功'),

                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->plan_code !== 'trial_plan'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn ($records) => $records->where('plan_code', '!=', 'trial_plan')->isNotEmpty()),
                ]),
            ]);
    }

    /**
     * @return array<string, array{label:string,options:array<string,string>}>
     */
    private static function groupedFeatureOptions(): array
    {
        $clientOptions = PassportClientService::make()->options();
        $groupedOptions = [];

        $features = Feature::query()
            ->with(['menu:id,menu_name'])
            ->orderBy('client_id')
            ->orderBy('menu_id')
            ->orderBy('feature_name')
            ->get(['id', 'client_id', 'menu_id', 'feature_name', 'feature_code']);

        foreach ($features as $feature) {
            $clientLabel = $clientOptions[(string) $feature->client_id] ?? (string) ($feature->client_id ?: '未知客户端');
            $menuLabel = $feature->menu?->menu_name ?: '未归类菜单';
            $groupLabel = sprintf('%s / %s', $clientLabel, $menuLabel);
            $groupKey = self::resolveGroupKey((string) $feature->client_id, (int) $feature->menu_id);

            $groupedOptions[$groupKey] ??= [
                'label' => $groupLabel,
                'options' => [],
            ];

            $groupedOptions[$groupKey]['options'][(string) $feature->id] = sprintf('%s (%s)', (string) $feature->feature_name, (string) $feature->feature_code);
        }

        return $groupedOptions;
    }

    /**
     * @return array<int, Section>
     */
    private static function buildFeatureBindingSchema(): array
    {
        $schema = [];

        foreach (self::groupedFeatureOptions() as $groupKey => $group) {
            $schema[] = Section::make($group['label'])
                ->compact()
                ->schema([
                    CheckboxList::make("feature_ids_by_group.{$groupKey}")
                        ->label('')
                        ->bulkToggleable()
                        ->columns(2)
                        ->searchable()
                        ->options($group['options']),
                ]);
        }

        return $schema;
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    private static function resolveFeatureSelectionsByGroup(mixed $record): array
    {
        $selectedByGroup = [];

        foreach ($record->features()->get(['oa_client_features.id', 'oa_client_features.client_id', 'oa_client_features.menu_id']) as $feature) {
            $groupKey = self::resolveGroupKey((string) $feature->client_id, (int) $feature->menu_id);
            $selectedByGroup[$groupKey] ??= [];
            $selectedByGroup[$groupKey][] = (string) $feature->id;
        }

        return [
            'feature_ids_by_group' => $selectedByGroup,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private static function flattenSelectedFeatureIds(array $data): array
    {
        $featureIds = [];

        foreach (($data['feature_ids_by_group'] ?? []) as $ids) {
            if (! is_array($ids)) {
                continue;
            }

            foreach ($ids as $id) {
                $featureIds[] = (int) $id;
            }
        }

        return array_values(array_unique(array_filter($featureIds, static fn (int $id): bool => $id > 0)));
    }

    private static function resolveGroupKey(string $clientId, int $menuId): string
    {
        return sprintf('%s__%s', $clientId ?: 'unknown', $menuId);
    }
}
