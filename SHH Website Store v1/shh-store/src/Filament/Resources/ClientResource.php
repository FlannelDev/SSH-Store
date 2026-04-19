<?php

namespace ShhStore\Filament\Resources;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use ShhStore\Filament\Resources\ClientResource\Pages;
use ShhStore\Filament\Resources\ClientResource\RelationManagers\LinkedServersRelationManager;
use ShhStore\Filament\Resources\ClientResource\RelationManagers\OrdersRelationManager;
use ShhStore\Models\StoreOrder;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $slug = 'store/clients';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('users.*')
            ->selectSub(
                StoreOrder::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id'),
                'total_orders'
            )
            ->selectSub(
                StoreOrder::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereIn('status', ['paid', 'active']),
                'active_orders'
            )
            ->selectSub(
                StoreOrder::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('user_id', 'users.id'),
                'total_spent'
            )
            ->selectSub(
                StoreOrder::selectRaw('MAX(created_at)')
                    ->whereColumn('user_id', 'users.id'),
                'last_order_at'
            )
            ->selectSub(
                StoreOrder::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('server_id'),
                'linked_servers_count'
            )
            ->selectSub(
                StoreOrder::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('server_id')
                    ->where('status', 'unpaid'),
                'unpaid_linked_servers_count'
            )
            ->selectSub(
                StoreOrder::selectRaw('MIN(bill_due_at)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('server_id')
                    ->whereNotNull('bill_due_at')
                    ->whereIn('status', ['active', 'paid', 'unpaid']),
                'next_linked_bill_due_at'
            )
            ->selectSub(
                StoreOrder::selectRaw('COUNT(DISTINCT node_id)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('server_id')
                    ->whereNotNull('node_id'),
                'connected_nodes_count'
            )
            ->selectSub(
                DB::table('shh_store_orders')
                    ->join('nodes', 'nodes.id', '=', 'shh_store_orders.node_id')
                    ->selectRaw('MIN(nodes.name)')
                    ->whereColumn('shh_store_orders.user_id', 'users.id')
                    ->whereNotNull('shh_store_orders.server_id')
                    ->whereNotNull('shh_store_orders.node_id'),
                'connected_node_name'
            )
            ->selectSub(
                StoreOrder::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNotNull('server_id')
                    ->whereIn('status', ['active', 'paid', 'unpaid', 'suspended']),
                'linked_billing_total'
            )
            ->whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('shh_store_orders')
                    ->whereColumn('shh_store_orders.user_id', 'users.id');
            });
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

            Section::make('Billing Summary')->schema([
                TextEntry::make('total_orders')
                    ->label('Total Orders')
                    ->numeric(),
                TextEntry::make('active_orders')
                    ->label('Active Orders')
                    ->numeric(),
                TextEntry::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD'),
                TextEntry::make('last_order_at')
                    ->label('Last Purchase')
                    ->dateTime()
                    ->placeholder('N/A'),
            ])->columns(4),

            Section::make('Connected Servers & Billing')->schema([
                TextEntry::make('linked_servers_count')
                    ->label('Connected Servers')
                    ->numeric(),
                TextEntry::make('connected_nodes_count')
                    ->label('Connected Nodes')
                    ->numeric(),
                TextEntry::make('connected_node_name')
                    ->label('Connected Node')
                    ->placeholder('N/A'),
                TextEntry::make('linked_billing_total')
                    ->label('Linked Billing Total')
                    ->money('USD'),
                TextEntry::make('unpaid_linked_servers_count')
                    ->label('Unpaid Linked Orders')
                    ->numeric(),
                TextEntry::make('next_linked_bill_due_at')
                    ->label('Next Linked Bill Due')
                    ->dateTime()
                    ->placeholder('N/A'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('SHH Username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('active_orders')
                    ->label('Active')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('connected_nodes_count')
                    ->label('Connected Nodes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('connected_node_name')
                    ->label('Connected Node')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('linked_billing_total')
                    ->label('Linked Billing')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('last_order_at')
                    ->label('Last Purchase')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('last_order_at', 'desc')
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
            OrdersRelationManager::class,
            LinkedServersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'view' => Pages\ViewClient::route('/{record}'),
        ];
    }
}
