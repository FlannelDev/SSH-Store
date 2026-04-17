<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources;

use App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource\Pages\CreateMediaAsset;
use App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource\Pages\EditMediaAsset;
use App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource\Pages\ListMediaAssets;
use App\Plugins\ShadowStore\Models\MediaAsset;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return 'Media Library';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Media Asset')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('alt_text')
                        ->label('Alt Text')
                        ->maxLength(255),
                    FileUpload::make('file_path')
                        ->label('Image')
                        ->required()
                        ->disk('public')
                        ->directory('store/media')
                        ->image()
                        ->imageEditor()
                        ->visibility('public')
                        ->helperText('Upload once here, then reuse the URL or select this image in other store admin screens.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('public_url')
                    ->label('URL')
                    ->copyable()
                    ->copyMessage('Image URL copied')
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/create'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }
}