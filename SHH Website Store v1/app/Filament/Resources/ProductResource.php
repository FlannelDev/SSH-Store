<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'Store';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Product Info')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                    ),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(2000)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tier')
                                    ->maxLength(100)
                                    ->placeholder('e.g. Standard, Premium'),
                            ])->columns(2),

                        Forms\Components\Section::make('Hardware Specs')
                            ->schema([
                                Forms\Components\TextInput::make('cpu')
                                    ->label('CPU')
                                    ->maxLength(255)
                                    ->placeholder('e.g. 4x CPU @ 4.8-6GHz'),
                                Forms\Components\TextInput::make('ram')
                                    ->label('RAM')
                                    ->maxLength(255)
                                    ->placeholder('e.g. 8 GB DDR5'),
                                Forms\Components\TextInput::make('storage')
                                    ->label('Storage')
                                    ->maxLength(255)
                                    ->placeholder('e.g. 200 GB NVMe'),
                            ])->columns(3),

                        Forms\Components\Section::make('Features')
                            ->schema([
                                Forms\Components\KeyValue::make('features')
                                    ->keyLabel('Feature')
                                    ->valueLabel('Detail')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing')
                            ->schema([
                                Forms\Components\TextInput::make('price_monthly')
                                    ->label('Monthly Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('price_quarterly')
                                    ->label('Quarterly Price')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('price_annually')
                                    ->label('Annual Price')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Visible on storefront')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured product')
                                    ->default(false),
                                Forms\Components\Toggle::make('in_stock')
                                    ->label('In stock')
                                    ->default(true),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('tier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cpu')
                    ->label('CPU')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ram')
                    ->label('RAM')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('storage')
                    ->label('Storage')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price_monthly')
                    ->money('usd')
                    ->sortable()
                    ->label('Price/mo'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean(),
                Tables\Columns\IconColumn::make('in_stock')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible'),
                Tables\Filters\TernaryFilter::make('in_stock')
                    ->label('In Stock'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
