<?php

namespace ShhStore\Filament\Resources\StoreProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use ShhStore\Filament\Resources\StoreProductResource;

class EditStoreProduct extends EditRecord
{
    protected static string $resource = StoreProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
