<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDedicatedMachines extends ListRecords
{
    protected static string $resource = DedicatedMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
