<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}