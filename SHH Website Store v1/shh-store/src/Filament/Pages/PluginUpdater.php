<?php

namespace ShhStore\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class PluginUpdater extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Plugin Updates';

    protected static ?string $navigationGroup = 'Store';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'SHH Store Updates';

    protected static string $view = 'shh-store::filament.pages.plugin-updater';

    public ?string $currentVersion = null;

    public ?string $latestVersion = null;

    public bool $checking = false;

    public bool $updateAvailable = false;

    public ?string $error = null;

    public function mount(): void
    {
        $this->currentVersion = $this->getInstalledVersion();
        $this->checkForUpdate();
    }

    protected function getInstalledVersion(): ?string
    {
        $pluginJsonPath = plugin_path('shh-store', 'plugin.json');

        if (!File::exists($pluginJsonPath)) {
            return null;
        }

        $data = json_decode(File::get($pluginJsonPath), true);

        return $data['version'] ?? null;
    }

    public function checkForUpdate(): void
    {
        $this->error = null;
        $this->updateAvailable = false;

        $pluginJsonPath = plugin_path('shh-store', 'plugin.json');

        if (!File::exists($pluginJsonPath)) {
            $this->error = 'plugin.json not found.';
            return;
        }

        $pluginData = json_decode(File::get($pluginJsonPath), true);
        $updateUrl = $pluginData['update_url'] ?? null;

        if (!$updateUrl) {
            $this->error = 'No update_url configured in plugin.json.';
            return;
        }

        try {
            $response = Http::timeout(10)->get($updateUrl);

            if (!$response->successful()) {
                $this->error = 'Failed to fetch update info (HTTP ' . $response->status() . ').';
                return;
            }

            $remote = $response->json();
            $this->latestVersion = $remote['version'] ?? null;

            if (!$this->latestVersion) {
                $this->error = 'Remote update.json missing version field.';
                return;
            }

            $this->updateAvailable = version_compare($this->latestVersion, $this->currentVersion, '>');
        } catch (\Exception $e) {
            $this->error = 'Could not reach update server: ' . $e->getMessage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check')
                ->label('Check for Updates')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->currentVersion = $this->getInstalledVersion();
                    $this->checkForUpdate();

                    if ($this->error) {
                        Notification::make()->title('Error')->body($this->error)->danger()->send();
                    } elseif ($this->updateAvailable) {
                        Notification::make()->title('Update Available')->body("Version {$this->latestVersion} is available.")->info()->send();
                    } else {
                        Notification::make()->title('Up to Date')->body('You are running the latest version.')->success()->send();
                    }
                }),
        ];
    }
}
