<?php

namespace ShhStore;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ShhStore\Filament\Pages\PluginUpdater;
use ShhStore\Filament\Resources\StoreCategoryResource;
use ShhStore\Filament\Resources\StoreOrderResource;
use ShhStore\Filament\Resources\StoreProductResource;

class ShhStorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'shh-store';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StoreCategoryResource::class,
            StoreProductResource::class,
            StoreOrderResource::class,
        ]);

        $panel->pages([
            PluginUpdater::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
