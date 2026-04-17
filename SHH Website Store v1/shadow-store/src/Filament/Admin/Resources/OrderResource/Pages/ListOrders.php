<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
