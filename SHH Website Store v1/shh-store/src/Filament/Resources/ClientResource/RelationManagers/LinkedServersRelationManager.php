<?php

namespace ShhStore\Filament\Resources\ClientResource\RelationManagers;

use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ShhStore\Filament\Resources\StoreOrderResource;
use ShhStore\Models\StoreOrder;

class LinkedServersRelationManager extends RelationManager
{
    protected static string $relationship = 'linkedServers';

    protected static ?string $title = 'Linked Servers & Billing';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function getRelationship(): Relation|Builder
    {
        return $this->getOwnerRecord()->linkedServers()
            ->with(['server', 'node', 'product'])
            ->orderByDesc('created_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('server.name')
                    ->label('Server')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('node.name')
                    ->label('Node')
                    ->placeholder('—'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Billing Amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->badge(),
                TextColumn::make('bill_due_at')
                    ->label('Bill Due')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('—')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('auto_suspend_status')
                    ->label('Auto Suspend')
                    ->state(function (StoreOrder $record): string {
                        if (filled($record->suspended_for_nonpayment_at) || $record->status === 'suspended') {
                            return 'Already suspended';
                        }

                        if (!$record->bill_due_at) {
                            return 'No due date';
                        }

                        if ($record->bill_due_at->isPast() && $record->status !== 'unpaid') {
                            return 'Awaiting unpaid status';
                        }

                        if ($record->isUnpaidForSuspension()) {
                            return 'Eligible now';
                        }

                        return 'In grace period';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Eligible now' => 'danger',
                        'Already suspended' => 'gray',
                        'No due date' => 'warning',
                        'Awaiting unpaid status' => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('suspended_for_nonpayment_at')
                    ->label('Suspended At')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('—')
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'active' => 'success',
                        'pending', 'processing' => 'warning',
                        'unpaid' => 'danger',
                        'cancelled', 'refunded' => 'danger',
                        'suspended' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('eligible_now')
                    ->label('Eligible now only')
                    ->query(function (Builder $query): Builder {
                        $threshold = now()->subDays(StoreOrder::suspensionDelayDays());

                        return $query
                            ->whereNotNull('bill_due_at')
                            ->where('bill_due_at', '<=', $threshold)
                            ->where('status', 'unpaid')
                            ->whereNull('suspended_for_nonpayment_at')
                            ->whereNotIn('status', ['cancelled', 'refunded', 'suspended']);
                    }),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (StoreOrder $record): void {
                        if ($record->suspendForNonPayment(force: true)) {
                            Notification::make()->title('Server suspended')->success()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Unable to suspend server')
                            ->body('Verify the linked server exists and can be suspended.')
                            ->danger()
                            ->send();
                    }),
                Action::make('unsuspend')
                    ->label('Unsuspend')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StoreOrder $record): bool => filled($record->suspended_for_nonpayment_at))
                    ->action(function (StoreOrder $record): void {
                        if ($record->releaseNonPaymentSuspension()) {
                            Notification::make()->title('Server unsuspended')->success()->send();
                            return;
                        }

                        Notification::make()->title('Unable to unsuspend server')->danger()->send();
                    }),
                Action::make('openOrder')
                    ->label('Open Order')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (StoreOrder $record): string => StoreOrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
