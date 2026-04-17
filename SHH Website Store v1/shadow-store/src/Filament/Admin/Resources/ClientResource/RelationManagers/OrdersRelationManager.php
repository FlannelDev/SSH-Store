<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\User;
use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource;
use App\Plugins\ShadowStore\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'storeOrders';

    protected static ?string $title = 'Orders';

    /**
     * Bypass the relationship existence check on the User model.
     * We provide a custom query instead.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    /**
     * Return a Builder scoped to this user's orders instead of using
     * a HasMany relationship on the core User model.
     */
    public function getRelationship(): Relation | Builder
    {
        return Order::where('user_id', $this->getOwnerRecord()->id);
    }

    public function table(Table $table): Table
    {
        $hasBillDueAt = SchemaFacade::hasColumn('store_orders', 'bill_due_at');

        return $table
            ->recordTitleAttribute('order_number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable(),
                TextColumn::make('slots')
                    ->label('Slots')
                    ->numeric(),
                TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->label('Payment'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('bill_due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not set')
                    ->visible($hasBillDueAt),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
