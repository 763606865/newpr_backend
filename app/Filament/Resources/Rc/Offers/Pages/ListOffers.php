<?php

namespace App\Filament\Resources\Rc\Offers\Pages;

use App\Enums\RcOfferStatus;
use App\Filament\Resources\Rc\Offers\OfferResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Offer;
use Filament\Resources\Pages\ListRecords;

class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Offer::class,
                'todayColumn' => 'sent_at',
                'todayLabel' => '今日发送 Offer',
                'statusCards' => [
                    ['label' => '已发送', 'value' => RcOfferStatus::Sent->value, 'color' => 'warning'],
                    ['label' => '已接受', 'value' => RcOfferStatus::Accepted->value, 'color' => 'success'],
                    ['label' => '已拒绝', 'value' => RcOfferStatus::Rejected->value, 'color' => 'danger'],
                ],
            ]),
        ];
    }
}
