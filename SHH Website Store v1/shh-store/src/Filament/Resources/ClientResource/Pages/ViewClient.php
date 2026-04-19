<?php

namespace ShhStore\Filament\Resources\ClientResource\Pages;

use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Filament\Resources\Pages\ViewRecord;
use ShhStore\Filament\Resources\ClientResource;

class ViewClient extends ViewRecord
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = ClientResource::class;
}
