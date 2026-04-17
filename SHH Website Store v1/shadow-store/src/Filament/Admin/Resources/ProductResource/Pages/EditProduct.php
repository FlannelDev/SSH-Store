<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveProduct')
                ->label('Save Product')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
            Actions\DeleteAction::make(),
        ];
    }
}
