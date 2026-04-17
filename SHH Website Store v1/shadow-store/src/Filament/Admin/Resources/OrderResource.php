<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Models\Server;
use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource\Pages\ListOrders;
use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource\Pages\EditOrder;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $hasBillDueAt = SchemaFacade::hasColumn('store_orders', 'bill_due_at');

        return $schema->components([
            Section::make('Order Specifications')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    Placeholder::make('specs.product_name')
                        ->label('Product')
                        ->content(fn (?Order $record) => $record?->product?->name ?? 'N/A'),

                    Placeholder::make('specs.plan_name')
                        ->label('Selected Box / Plan')
                        ->content(function (?Order $record): string {
                            if (!$record) {
                                return 'N/A';
                            }

                            $specs = $record->getOrderSpecs();

                            return $specs['plan_name'] ?? 'N/A';
                        }),
                    
                    Placeholder::make('specs.game')
                        ->label('Game Type')
                        ->content(fn (?Order $record) => ucwords(str_replace('-', ' ', $record?->product?->game ?? 'N/A'))),
                    
                    Placeholder::make('specs.category')
                        ->label('Category')
                        ->content(fn (?Order $record) => ucfirst($record?->product?->category ?? 'N/A')),
                    
                    Placeholder::make('specs.memory')
                        ->label('Memory')
                        ->content(function (?Order $record): string {
                            if (!$record) {
                                return 'N/A';
                            }

                            $specs = $record->getOrderSpecs();

                            return isset($specs['memory_gb']) ? $specs['memory_gb'] . ' GB' : 'N/A';
                        }),
                    
                    Placeholder::make('specs.cpu')
                        ->label('CPU Units')
                        ->content(function (?Order $record): string {
                            if (!$record) return 'N/A';
                            $specs = $record->getOrderSpecs();
                            return (string)($specs['cpu_units'] ?? 'N/A');
                        }),
                    
                    Placeholder::make('specs.disk')
                        ->label('Disk Storage')
                        ->content(fn (?Order $record) => '1000 GB'),
                    
                    Placeholder::make('specs.slots')
                        ->label('Player Slots')
                        ->content(function (?Order $record): string {
                            if (!$record) return 'N/A';
                            $specs = $record->getOrderSpecs();
                            return (string)($specs['slots'] ?? 'N/A');
                        }),
                    
                    Placeholder::make('specs.billing_type')
                        ->label('Billing Type')
                        ->content(function (?Order $record): string {
                            if (!$record) return 'N/A';
                            $specs = $record->getOrderSpecs();
                            return $specs['billing_type'] ?? 'N/A';
                        })
                        ->columnSpan(2),
                ]),

            Section::make('Order Details')->schema([
                TextInput::make('order_number')
                    ->disabled(),
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->disabled(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ])
                    ->native(true)
                    ->required(),
            ])->columns(2),
            
            Section::make('Payment')->schema([
                TextInput::make('subtotal')
                    ->disabled()
                    ->prefix('$'),
                TextInput::make('tax')
                    ->disabled()
                    ->prefix('$'),
                TextInput::make('total')
                    ->disabled()
                    ->prefix('$'),
                TextInput::make('payment_method')
                    ->disabled(),
                TextInput::make('payment_id')
                    ->disabled(),
                TextInput::make('coupon_code')
                    ->label('Coupon Used')
                    ->disabled()
                    ->placeholder('None'),
            ])->columns(3),
            
            Section::make('Server Assignment')->schema([
                Select::make('server_id')
                    ->label('Assigned Server')
                    ->native(true)
                    ->placeholder('Not assigned')
                    ->options(function (?Order $record): array {
                        $query = Server::query()->orderBy('name');

                        if ($record?->user_id) {
                            $query->where('owner_id', $record->user_id);
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->helperText('Assign or clear the server directly here, then click Save.'),
                Placeholder::make('server.name')
                    ->label('Server Name')
                    ->content(fn (?Order $record) => $record?->server?->name ?? 'Not assigned')
                    ->visible(fn (?Order $record) => $record?->server_id),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('bill_due_at')
                    ->label('Bill Due Date')
                    ->helperText('Manual billing due date. Past dates are allowed.')
                    ->visible($hasBillDueAt),
                Toggle::make('auto_renew'),
            ])->columns(3),
            
            Textarea::make('notes')
                ->columnSpanFull()
                ->rows(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        $hasBillDueAt = SchemaFacade::hasColumn('store_orders', 'bill_due_at');

        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->sortable(),
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
                    }),
                TextColumn::make('payment_method')
                    ->badge(),
                TextColumn::make('coupon_code')
                    ->label('Coupon')
                    ->badge()
                    ->placeholder('None')
                    ->sortable(),
                TextColumn::make('bill_due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not set')
                    ->visible($hasBillDueAt),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
