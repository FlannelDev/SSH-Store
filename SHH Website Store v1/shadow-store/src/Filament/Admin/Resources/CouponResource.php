<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Plugins\ShadowStore\Models\Coupon;
use App\Plugins\ShadowStore\Filament\Admin\Resources\CouponResource\Pages\ListCoupons;
use App\Plugins\ShadowStore\Filament\Admin\Resources\CouponResource\Pages\CreateCoupon;
use App\Plugins\ShadowStore\Filament\Admin\Resources\CouponResource\Pages\EditCoupon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coupon Details')->schema([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->suffixAction(
                        Action::make('generate')
                            ->icon('heroicon-o-sparkles')
                            ->action(fn (Set $set) => $set('code', strtoupper(Str::random(8))))
                    ),
                TextInput::make('description'),
                Select::make('type')
                    ->options([
                        'percentage' => 'Percentage Discount',
                        'fixed' => 'Fixed Amount',
                        'affiliate' => 'Affiliate Tracking (No Discount)',
                    ])
                    ->required()
                    ->native(true)
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (Set $set, $state): void {
                        if ($state === 'affiliate') {
                            $set('value', 0);
                            $set('first_month_only', false);
                        }
                    }),
                TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->disabled(fn ($get) => $get('type') === 'affiliate')
                    ->dehydrated(true)
                    ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : '')
                    ->prefix(fn ($get) => $get('type') === 'fixed' ? '$' : '')
                    ->helperText(fn ($get) => $get('type') === 'affiliate'
                        ? 'Affiliate tracking coupons apply no discount and are used for referral attribution.'
                        : null),
            ])->columns(2),
            
            Section::make('Limits')->schema([
                TextInput::make('max_uses')
                    ->numeric()
                    ->placeholder('Unlimited'),
                TextInput::make('max_uses_per_user')
                    ->numeric()
                    ->default(1),
                TextInput::make('min_order')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('No minimum'),
            ])->columns(3),
            
            Section::make('Validity')->schema([
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_active')
                    ->default(true),
            ])->columns(3),

            Section::make('Behavior')->schema([
                Toggle::make('first_month_only')
                    ->label('First Month Only')
                    ->helperText('When enabled the discount applies to the initial payment only. Disable to apply the discount to the full billing period total.')
                    ->visible(fn ($get) => $get('type') !== 'affiliate')
                    ->default(true),
            ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'success',
                        'affiliate' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                        'affiliate' => 'Affiliate',
                        default => ucfirst($state),
                    }),
                TextColumn::make('value')
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage' 
                        ? $record->value . '%' 
                        : ($record->type === 'affiliate'
                            ? 'Tracking only'
                            : '$' . number_format($record->value, 2))),
                TextColumn::make('uses')
                    ->label('Used')
                    ->formatStateUsing(fn ($record) => $record->uses . ($record->max_uses ? '/' . $record->max_uses : '')),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('first_month_only')
                    ->label('1st Month Only')
                    ->boolean(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
