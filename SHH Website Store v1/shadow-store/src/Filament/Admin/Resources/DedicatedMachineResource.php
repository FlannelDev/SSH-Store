<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Plugins\ShadowStore\Models\DedicatedMachine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DedicatedMachineResource extends Resource
{
    protected static ?string $model = DedicatedMachine::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';
    protected static ?string $navigationLabel = 'Dedicated Machines';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->rows(2),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Toggle::make('is_featured')->default(false),
            ])->columns(2),

            Forms\Components\Section::make('CPU')->schema([
                Forms\Components\TextInput::make('cpu_model')->required(),
                Forms\Components\Select::make('cpu_type')->options([
                    'gaming' => 'Gaming (High single-thread)',
                    'workstation' => 'Workstation (Balanced)',
                    'server' => 'Server (High core count)',
                    'budget' => 'Budget',
                ])->required(),
                Forms\Components\TextInput::make('cpu_cores')->numeric()->required(),
                Forms\Components\TextInput::make('cpu_threads')->numeric()->required(),
                Forms\Components\TextInput::make('cpu_speed')->numeric()->suffix('GHz')->required(),
                Forms\Components\TextInput::make('cpu_score')->numeric()->helperText('PassMark single-thread score'),
            ])->columns(3),

            Forms\Components\Section::make('Memory & Storage')->schema([
                Forms\Components\TextInput::make('ram_gb')->numeric()->required()->suffix('GB'),
                Forms\Components\Select::make('ram_type')->options([
                    'DDR3' => 'DDR3',
                    'DDR4' => 'DDR4',
                    'DDR5' => 'DDR5',
                ])->required(),
                Forms\Components\TextInput::make('storage_config')->required()->placeholder('2x1TB NVMe'),
                Forms\Components\TextInput::make('storage_total_gb')->numeric()->required()->suffix('GB'),
                Forms\Components\Select::make('storage_type')->options([
                    'HDD' => 'HDD',
                    'SSD' => 'SSD',
                    'NVMe' => 'NVMe',
                ])->required(),
            ])->columns(3),

            Forms\Components\Section::make('Network')->schema([
                Forms\Components\Select::make('network_speed')->options([
                    '1Gbps' => '1 Gbps',
                    '10Gbps' => '10 Gbps',
                ])->default('1Gbps'),
                Forms\Components\TextInput::make('bandwidth_tb')->numeric()->default(100)->suffix('TB'),
                Forms\Components\TextInput::make('ip_addresses')->numeric()->default(1),
            ])->columns(3),

            Forms\Components\Section::make('Pricing')->schema([
                Forms\Components\TextInput::make('cost_price')->numeric()->prefix('$')->required()
                    ->helperText('Your cost from provider'),
                Forms\Components\TextInput::make('sell_price')->numeric()->prefix('$')->required()
                    ->helperText('Price to customer'),
                Forms\Components\TextInput::make('setup_fee')->numeric()->prefix('$')->default(0),
                Forms\Components\Placeholder::make('profit')
                    ->content(fn ($record) => $record ? '$' . number_format($record->sell_price - $record->cost_price, 2) . '/mo profit (' . round((($record->sell_price - $record->cost_price) / $record->cost_price) * 100, 1) . '% markup)' : '-'),
            ])->columns(4),

            Forms\Components\Section::make('Provider')->schema([
                Forms\Components\TextInput::make('provider')->default('ReliableSite'),
                Forms\Components\TextInput::make('provider_sku')->placeholder('Provider product ID'),
                Forms\Components\TextInput::make('datacenter_location')->placeholder('NYC / Miami / LA'),
                Forms\Components\Toggle::make('rapid_deploy')->default(true)->helperText('10 min setup'),
                Forms\Components\TextInput::make('setup_time_hours')->numeric()->default(1),
                Forms\Components\TextInput::make('stock')->numeric()->placeholder('Unlimited'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('★'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cpu_model')->searchable()->limit(20),
                Tables\Columns\BadgeColumn::make('cpu_type')
                    ->colors([
                        'success' => 'gaming',
                        'warning' => 'workstation',
                        'gray' => 'server',
                        'danger' => 'budget',
                    ]),
                Tables\Columns\TextColumn::make('ram_gb')->suffix('GB')->sortable(),
                Tables\Columns\TextColumn::make('storage_config')->limit(15),
                Tables\Columns\TextColumn::make('cost_price')->money('usd')->label('Cost')->sortable(),
                Tables\Columns\TextColumn::make('sell_price')->money('usd')->label('Price')->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->getStateUsing(fn ($record) => '$' . number_format($record->sell_price - $record->cost_price, 0))
                    ->label('Profit'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cpu_type')
                    ->options([
                        'gaming' => 'Gaming',
                        'workstation' => 'Workstation',
                        'server' => 'Server',
                        'budget' => 'Budget',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource\Pages\ListDedicatedMachines::route('/'),
            'create' => \App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource\Pages\CreateDedicatedMachine::route('/create'),
            'edit' => \App\Plugins\ShadowStore\Filament\Admin\Resources\DedicatedMachineResource\Pages\EditDedicatedMachine::route('/{record}/edit'),
        ];
    }
}
