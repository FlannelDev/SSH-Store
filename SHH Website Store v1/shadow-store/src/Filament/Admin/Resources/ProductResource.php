<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Models\Egg;
use App\Models\Node;
use App\Plugins\ShadowStore\Models\MediaAsset;
use App\Plugins\ShadowStore\Models\Product;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource\Pages\ListProducts;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource\Pages\CreateProduct;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource\Pages\EditProduct;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Product')->tabs([
                Tabs\Tab::make('General')->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->rows(3),
                    TagsInput::make('features')
                        ->placeholder('Add feature'),
                    Select::make('image_asset_id')
                        ->label('Shared Image')
                        ->options(fn () => MediaAsset::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Choose an image from the shared Store > Media Library.'),
                    TextInput::make('image')
                        ->placeholder('https://... or storage path')
                        ->helperText('Optional fallback if you do not want to use the shared media library.'),
                    Select::make('category')
                        ->options([
                            'game-server' => 'Game Server',
                            'dedicated' => 'Dedicated Machine',
                            'vps' => 'VPS',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Select::make('game')
                        ->options([
                            'arma-reforger' => 'Arma Reforger',
                            'arma3' => 'Arma 3',
                            'dayz' => 'DayZ',
                            'minecraft' => 'Minecraft',
                            'rust' => 'Rust',
                            'valheim' => 'Valheim',
                            'ark' => 'ARK',
                            'csgo' => 'Counter-Strike 2',
                            'gmod' => "Garry's Mod",
                            'project-zomboid' => 'Project Zomboid',
                            'palworld' => 'Palworld',
                            'other' => 'Other',
                        ]),
                ]),
                
                Tabs\Tab::make('Server Config')->schema([
                    Select::make('egg_id')
                        ->label('Egg')
                        ->relationship('egg', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Select the egg to use for server deployment. Users will be asked to configure any user-editable variables.'),
                    
                    Section::make('Node Selection')
                        ->description('Choose which nodes can be used for deploying servers with this product')
                        ->schema([
                            CheckboxList::make('node_ids')
                                ->label('Allowed Nodes (leave empty for all)')
                                ->options(Node::pluck('name', 'id'))
                                ->columns(3)
                                ->helperText('Select specific nodes to deploy to. Leave empty to allow all nodes.'),
                            
                            CheckboxList::make('excluded_node_ids')
                                ->label('Excluded Nodes')
                                ->options(Node::pluck('name', 'id'))
                                ->columns(3)
                                ->helperText('Select nodes that should NOT be used for this product.'),
                        ]),
                ]),
                
                Tabs\Tab::make('Pricing')->schema([
                    Select::make('billing_type')
                        ->options([
                            'monthly' => 'Fixed Monthly',
                            'slots' => 'Per-Slot / Box Tier Pricing',
                            'onetime' => 'One-Time Purchase',
                        ])
                        ->required()
                        ->live()
                        ->helperText('Arma Reforger products use preset Shadow Box tiers on the storefront while still storing a slot-based backend product.'),
                    TextInput::make('base_price')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Base price for fixed monthly billing'),
                    TextInput::make('price_per_slot')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Legacy backend price-per-slot. Arma Reforger storefront pricing is shown to customers as preset Shadow Box tiers.'),
                    Grid::make(4)->schema([
                        TextInput::make('min_slots')
                            ->numeric(),
                        TextInput::make('max_slots')
                            ->numeric(),
                        TextInput::make('slot_increment')
                            ->numeric()
                            ->default(1),
                        TextInput::make('default_slots')
                            ->numeric(),
                    ]),
                ]),
                
                Tabs\Tab::make('Resources')->schema([
                    Section::make('Base Resources')
                        ->description('Fixed resources or base values for slot calculation')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('memory')
                                    ->numeric()
                                    ->suffix('MB')
                                    ->helperText('Fixed RAM or base RAM'),
                                TextInput::make('disk')
                                    ->numeric()
                                    ->suffix('MB')
                                    ->helperText('Storage in MB'),
                                TextInput::make('cpu')
                                    ->numeric()
                                    ->suffix('%')
                                    ->helperText('CPU limit (100% = 1 core)'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('swap')
                                    ->numeric()
                                    ->suffix('MB')
                                    ->default(0),
                                TextInput::make('io')
                                    ->numeric()
                                    ->default(500)
                                    ->helperText('IO weight (10-1000)'),
                            ]),
                        ]),
                    Section::make('Per-Slot Resources')
                        ->description('Resources allocated per slot (for slot-based products)')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('memory_per_slot')
                                    ->numeric()
                                    ->suffix('MB'),
                                TextInput::make('disk_per_slot')
                                    ->numeric()
                                    ->suffix('MB'),
                                TextInput::make('cpu_per_slot')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                        ]),
                    Section::make('Limits')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('databases')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('backups')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('allocations')
                                    ->numeric()
                                    ->default(1),
                            ]),
                        ]),
                ]),
                
                Tabs\Tab::make('Settings')->schema([
                    Toggle::make('is_active')
                        ->default(true),
                    Toggle::make('is_featured'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    TextInput::make('stock')
                        ->numeric()
                        ->placeholder('Unlimited'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('game')
                    ->searchable(),
                TextColumn::make('egg.name')
                    ->label('Egg'),
                TextColumn::make('admin_pricing_model')
                    ->label('Pricing Model')
                    ->badge()
                    ->color(fn (Product $record): string => $record->usesPresetBoxTiers() ? 'warning' : 'gray'),
                TextColumn::make('admin_price_display')
                    ->label('Price Display'),
                TextColumn::make('admin_tier_summary')
                    ->label('Box Versions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category'),
                SelectFilter::make('game'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
