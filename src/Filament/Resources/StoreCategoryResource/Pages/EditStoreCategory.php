<?php

namespace ShhStore\Filament\Resources\StoreCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use ShhStore\Filament\Resources\StoreCategoryResource;

class EditStoreCategory extends EditRecord
{
    protected static string $resource = StoreCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
