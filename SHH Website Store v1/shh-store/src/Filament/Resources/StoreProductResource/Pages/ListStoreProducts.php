<?php

namespace ShhStore\Filament\Resources\StoreProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use ShhStore\Filament\Resources\StoreProductResource;

class ListStoreProducts extends ListRecords
{
    protected static string $resource = StoreProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
