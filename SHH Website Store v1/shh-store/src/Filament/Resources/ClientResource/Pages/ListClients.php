<?php

namespace ShhStore\Filament\Resources\ClientResource\Pages;

use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Filament\Resources\Pages\ListRecords;
use ShhStore\Filament\Resources\ClientResource;

class ListClients extends ListRecords
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = ClientResource::class;
}
