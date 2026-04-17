<?php

namespace App\Plugins\ShadowStore;

use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource;
use App\Plugins\ShadowStore\Filament\Admin\Resources\MediaAssetResource;
use App\Plugins\ShadowStore\Filament\Admin\Resources\ProductResource;
use App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource;
use App\Plugins\ShadowStore\Filament\Admin\Resources\CouponResource;
use App\Plugins\ShadowStore\Filament\Admin\Pages\StoreSettings;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Actions\Action;

class ShadowStorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'shadow-store';
    }

    public function register(Panel $panel): void
    {
        $panelId = $panel->getId();
        
        // Add admin resources only for admin panel
        if ($panelId === 'admin') {
            $panel->resources([
                ClientResource::class,
                ProductResource::class,
                OrderResource::class,
                CouponResource::class,
                MediaAssetResource::class,
            ]);
            $panel->pages([
                StoreSettings::class,
            ]);
        }
        
        // Add Store link to user menu for app panel
        if ($panelId === 'app') {
            $panel->userMenuItems([
                'store' => Action::make('store')
                    ->label('Store')
                    ->url('/store')
                    ->icon('heroicon-o-shopping-bag'),
                'wiki' => Action::make('wiki')
                    ->label('Wiki')
                    ->url('/wiki')
                    ->icon('heroicon-o-book-open'),
                'billing' => Action::make('billing')
                    ->label('Billing')
                    ->url('/store/billing')
                    ->icon('heroicon-o-credit-card'),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static();
    }
}
