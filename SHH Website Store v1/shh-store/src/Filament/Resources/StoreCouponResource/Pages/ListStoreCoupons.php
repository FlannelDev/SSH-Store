<?php

namespace ShhStore\Filament\Resources\StoreCouponResource\Pages;

use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use ShhStore\Filament\Resources\StoreCouponResource;

class ListStoreCoupons extends ListRecords
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = StoreCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
