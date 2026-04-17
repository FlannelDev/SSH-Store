<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource;
use App\Models\Server;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignServer')
                ->label('Assign Server')
                ->icon('heroicon-o-server-stack')
                ->form([
                    Forms\Components\Select::make('server_id')
                        ->label('Select Server to Assign')
                        ->options(function () {
                            return Server::query()
                                ->where('owner_id', $this->record->user_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->helperText('Choose from servers owned by this user'),
                ])
                ->action(function (array $data): void {
                    $server = Server::find($data['server_id']);
                    if ($server) {
                        $this->record->assignServer($server);
                        $this->refreshFormData(['server_id']);
                    }
                })
                ->modalHeading('Assign Server to Order')
                ->modalButton('Assign')
                ->visible(fn() => !$this->record->server_id)
                ->successNotificationTitle('Server assigned successfully'),

            Actions\Action::make('unassignServer')
                ->label('Unassign Server')
                ->icon('heroicon-o-x-mark')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['server_id' => null]);
                    $this->refreshFormData(['server_id']);
                })
                ->visible(fn() => $this->record->server_id)
                ->color('danger')
                ->successNotificationTitle('Server unassigned'),

            Actions\DeleteAction::make(),
        ];
    }
}


