<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDedicatedMachine extends EditRecord
{
    protected static string $resource = DedicatedMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
