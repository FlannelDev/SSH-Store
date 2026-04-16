<?php

namespace ShhStore\Filament\Resources\StoreOrderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use ShhStore\Filament\Resources\StoreOrderResource;

class EditStoreOrder extends EditRecord
{
    protected static string $resource = StoreOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Orders')
                ->url(StoreOrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
