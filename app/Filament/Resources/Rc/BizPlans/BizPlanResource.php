<?php

namespace App\Filament\Resources\Rc\BizPlans;

use App\Filament\Resources\Rc\BizPlans\Pages\CreateBizPlan;
use App\Filament\Resources\Rc\BizPlans\Pages\EditBizPlan;
use App\Filament\Resources\Rc\BizPlans\Pages\ListBizPlans;
use App\Filament\Resources\Rc\BizPlans\Schemas\BizPlanForm;
use App\Filament\Resources\Rc\BizPlans\Tables\BizPlansTable;
use App\Models\Rc\BizPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BizPlanResource extends Resource
{
    protected static ?string $model = BizPlan::class;

    protected static ?string $label = '商品';

    protected static ?string $pluralLabel = '商品配置';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'plan_name';

    public static function form(Schema $schema): Schema
    {
        return BizPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BizPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBizPlans::route('/'),
            'create' => CreateBizPlan::route('/create'),
            'edit' => EditBizPlan::route('/{record}/edit'),
        ];
    }
}
