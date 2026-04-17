<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Models\User;
use App\Plugins\ShadowStore\Models\ClientCredit;
use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Models\ServerBilling;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\Pages\CreateClient;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\Pages\ListClients;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\Pages\ViewClient;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\RelationManagers\ServersRelationManager;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\RelationManagers\OrdersRelationManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $modelLabel = 'Client';
    protected static ?string $pluralModelLabel = 'Clients';
    protected static ?string $slug = 'store/clients';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->select('users.*')
            ->selectSub(
                Order::selectRaw('COUNT(*)')->whereColumn('user_id', 'users.id'),
                'total_orders'
            )
            ->selectSub(
                Order::selectRaw('COUNT(*)')->whereColumn('user_id', 'users.id')->where('status', 'paid'),
                'active_orders'
            )
            ->selectSub(
                Order::selectRaw('COALESCE(SUM(total), 0)')->whereColumn('user_id', 'users.id'),
                'total_spent'
            )
            ->selectSub(
                Order::selectRaw('MAX(created_at)')->whereColumn('user_id', 'users.id'),
                'last_order_at'
            );

        if (SchemaFacade::hasTable('store_client_credits')) {
            $query->selectSub(
                ClientCredit::selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('user_id', 'users.id'),
                'credit_balance'
            );
        } else {
            $query->selectRaw('0 as credit_balance');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client Details')->schema([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ])->columns(3),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client Information')->schema([
                TextEntry::make('username')
                    ->label('Username'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('created_at')
                    ->label('Member Since')
                    ->dateTime(),
            ])->columns(3),

            Section::make('Store Activity')->schema([
                TextEntry::make('total_orders')
                    ->label('Total Orders')
                    ->numeric(),
                TextEntry::make('servers_count')
                    ->label('Total Servers')
                    ->state(fn (User $record): int => $record->servers()->count())
                    ->numeric(),
                TextEntry::make('active_orders')
                    ->label('Active (Paid) Orders')
                    ->numeric(),
                TextEntry::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD'),
                TextEntry::make('credit_balance')
                    ->label('Credit Balance')
                    ->money('USD'),
                TextEntry::make('last_order_at')
                    ->label('Last Purchase')
                    ->dateTime()
                    ->placeholder('N/A'),
            ])->columns(5),

            Section::make('Server Billing')->schema([
                RepeatableEntry::make('server_billing_rows')
                    ->label(false)
                    ->contained(false)
                    ->state(function (User $record): array {
                        $servers = $record->servers()->orderBy('name')->get();
                        $billings = SchemaFacade::hasTable('store_server_billings')
                            ? ServerBilling::query()->whereIn('server_id', $servers->pluck('id'))->get()->keyBy('server_id')
                            : collect();

                        return $servers->map(function ($server) use ($billings) {
                            $billing = $billings->get($server->id);

                            return [
                                'name' => $server->name,
                                'uuid_short' => $server->uuid_short,
                                'node_name' => $server->node?->name ?? 'Unknown',
                                'billing_amount_display' => filled($billing?->billing_amount)
                                    ? '$' . number_format((float) $billing->billing_amount, 2)
                                    : 'Not set',
                                'node_amount_display' => filled($billing?->node_amount)
                                    ? '$' . number_format((float) $billing->node_amount, 2)
                                    : 'Not set',
                                'bill_due_at_display' => $billing?->bill_due_at?->format('M j, Y g:i A') ?? 'Not set',
                                'billing_status' => !empty($billing?->suspended_for_nonpayment_at)
                                    ? 'Suspended for non-payment'
                                    : ($billing?->bill_due_at && $billing->bill_due_at->isPast() ? 'Past due' : 'Tracked'),
                            ];
                        })->all();
                    })
                    ->schema([
                        TextEntry::make('name')
                            ->label('Server'),
                        TextEntry::make('uuid_short')
                            ->label('Identifier'),
                        TextEntry::make('node_name')
                            ->label('Node'),
                        TextEntry::make('billing_amount_display')
                            ->label('Server Amount'),
                        TextEntry::make('node_amount_display')
                            ->label('Node Amount'),
                        TextEntry::make('bill_due_at_display')
                            ->label('Due Date'),
                        TextEntry::make('billing_status')
                            ->label('Billing Status'),
                    ])
                    ->columns(7),
            ])->visible(fn (User $record): bool => $record->servers()->exists()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('servers_count')
                    ->label('Servers')
                    ->counts('servers')
                    ->sortable(),
                TextColumn::make('active_orders')
                    ->label('Active')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('credit_balance')
                    ->label('Credit')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('last_order_at')
                    ->label('Last Purchase')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('last_order_at', 'desc')
            ->toolbarActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('viewClient')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record): string => static::getUrl('view', ['record' => $record])),
            ]);
    }

    /** @return class-string<RelationManager>[] */
    public static function getRelationManagers(): array
    {
        return [
            ServersRelationManager::class,
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
        ];
    }
}
