<?php

namespace ShhStore\Filament\Resources\StoreCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use ShhStore\Filament\Resources\StoreCategoryResource;

class ListStoreCategories extends ListRecords
{
    protected static string $resource = StoreCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
