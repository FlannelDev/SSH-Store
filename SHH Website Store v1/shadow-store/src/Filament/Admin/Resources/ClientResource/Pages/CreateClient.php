<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource;
use App\Services\Users\UserCreationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected static bool $canCreateAnother = false;

    private UserCreationService $service;

    public function boot(UserCreationService $service): void
    {
        $this->service = $service;
    }

    protected function prepareForValidation($attributes): array
    {
        $attributes['data']['email'] = mb_strtolower($attributes['data']['email']);

        return $attributes;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['root_admin'] = false;

        return $this->service->handle($data);
    }
}
