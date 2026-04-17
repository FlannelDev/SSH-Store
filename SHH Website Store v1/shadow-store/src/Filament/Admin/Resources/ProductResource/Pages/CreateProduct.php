<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
