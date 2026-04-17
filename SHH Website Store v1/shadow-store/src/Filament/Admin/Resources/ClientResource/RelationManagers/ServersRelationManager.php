<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Filament\Admin\Resources\Servers\ServerResource;
use App\Models\Server;
use App\Plugins\ShadowStore\Models\ServerBilling;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class ServersRelationManager extends RelationManager
{
    protected static string $relationship = 'servers';

    protected static ?string $title = 'Servers';

    public function table(Table $table): Table
    {
        $hasServerBilling = SchemaFacade::hasTable('store_server_billings');

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($hasServerBilling): Builder {
                $query->select('servers.*');

                if ($hasServerBilling) {
                    $query->selectSub(
                        ServerBilling::query()
                            ->select('bill_due_at')
                            ->whereColumn('server_id', 'servers.id')
                            ->limit(1),
                        'bill_due_at'
                    );
                }

                return $query;
            })
            ->recordTitleAttribute('name')
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uuid_short')
                    ->label('Identifier')
                    ->searchable(),
                TextColumn::make('node.name')
                    ->label('Node')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->placeholder('Active'),
                TextColumn::make('bill_due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not set')
                    ->visible($hasServerBilling),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('setDueDate')
                    ->label('Set Due Date')
                    ->icon('heroicon-o-calendar-days')
                    ->visible($hasServerBilling)
                    ->fillForm(function (Server $record): array {
                        $billing = ServerBilling::query()->where('server_id', $record->id)->first();

                        return [
                            'bill_due_at' => $billing?->bill_due_at,
                        ];
                    })
                    ->schema([
                        DateTimePicker::make('bill_due_at')
                            ->label('Bill Due Date')
                            ->helperText('Past dates are allowed for existing servers.'),
                    ])
                    ->action(function (Server $record, array $data): void {
                        ServerBilling::query()->updateOrCreate(
                            ['server_id' => $record->id],
                            [
                                'user_id' => $record->owner_id,
                                'bill_due_at' => $data['bill_due_at'] ?? null,
                            ]
                        );
                    }),
                Action::make('manageServer')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Server $record): string => ServerResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
