<?php

namespace App\Filament\Resources\Rc\SchoolActivities\Pages;

use App\Enums\RcSchoolActivityType;
use App\Filament\Resources\Rc\SchoolActivities\SchoolActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSchoolActivities extends ListRecords
{
    protected static string $resource = SchoolActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<int, Tab>
     */
    public function getTabs(): array
    {
        return [
            RcSchoolActivityType::DualSelection->value => Tab::make(RcSchoolActivityType::DualSelection->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->ofType(RcSchoolActivityType::DualSelection)),
            RcSchoolActivityType::Presentation->value => Tab::make(RcSchoolActivityType::Presentation->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->ofType(RcSchoolActivityType::Presentation)),
            RcSchoolActivityType::JobFair->value => Tab::make(RcSchoolActivityType::JobFair->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->ofType(RcSchoolActivityType::JobFair)),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('organizer');
    }
}
