<?php

namespace ShhStore\Filament\Resources\ClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ShhStore\Models\StoreOrder;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'storeOrders';

    protected static ?string $title = 'Orders';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function getRelationship(): Relation|Builder
    {
        return StoreOrder::where('user_id', $this->getOwnerRecord()->id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product Purchased')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date Purchased')
                    ->dateTime('M j, Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('billing_cycle')
                    ->label('Billing Cycle')
                    ->badge(),
                TextColumn::make('bill_due_at')
                    ->label('Monthly Due Date')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'active' => 'success',
                        'pending', 'processing' => 'warning',
                        'cancelled', 'refunded' => 'danger',
                        'suspended' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge(),
            ]);
    }
}
