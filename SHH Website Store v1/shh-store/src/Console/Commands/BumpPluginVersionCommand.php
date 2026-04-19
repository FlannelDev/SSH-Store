<?php

namespace ShhStore\Console\Commands;

use Illuminate\Console\Command;

class BumpPluginVersionCommand extends Command
{
    protected $signature = 'shh-store:bump-version {part=patch : major|minor|patch}';

    protected $description = 'Bump plugin version in shh-store/plugin.json and shh-store/update.json together.';

    public function handle(): int
    {
        $part = strtolower((string) $this->argument('part'));

        if (!in_array($part, ['major', 'minor', 'patch'], true)) {
            $this->error('Invalid part. Use: major, minor, or patch.');
            return self::FAILURE;
        }

        $pluginPath = base_path('plugins/shh-store/plugin.json');
        $updatePath = base_path('plugins/shh-store/update.json');

        // Workspace fallback (for local dev paths where plugin isn't mounted under /plugins).
        if (!is_file($pluginPath) || !is_file($updatePath)) {
            $pluginPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'plugin.json';
            $updatePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'update.json';
        }

        if (!is_file($pluginPath) || !is_file($updatePath)) {
            $this->error('Could not locate plugin.json/update.json.');
            return self::FAILURE;
        }

        $pluginRaw = file_get_contents($pluginPath);
        $updateRaw = file_get_contents($updatePath);

        if ($pluginRaw === false || $updateRaw === false) {
            $this->error('Failed to read version files.');
            return self::FAILURE;
        }

        $plugin = json_decode($pluginRaw, true);
        $update = json_decode($updateRaw, true);

        if (!is_array($plugin) || !is_array($update)) {
            $this->error('Invalid JSON in version files.');
            return self::FAILURE;
        }

        $current = (string) ($plugin['version'] ?? '0.0.0');
        $next = $this->bump($current, $part);

        $plugin['version'] = $next;

        if (!isset($update['*']) || !is_array($update['*'])) {
            $update['*'] = [];
        }

        $update['*']['version'] = $next;

        $pluginEncoded = json_encode($plugin, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $updateEncoded = json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($pluginEncoded === false || $updateEncoded === false) {
            $this->error('Failed to encode updated JSON.');
            return self::FAILURE;
        }

        $pluginWritten = file_put_contents($pluginPath, $pluginEncoded . PHP_EOL);
        $updateWritten = file_put_contents($updatePath, $updateEncoded . PHP_EOL);

        if ($pluginWritten === false || $updateWritten === false) {
            $this->error('Failed to write updated version files.');
            return self::FAILURE;
        }

        $this->info("Version bumped: {$current} -> {$next}");

        return self::SUCCESS;
    }

    protected function bump(string $version, string $part): string
    {
        [$major, $minor, $patch] = array_map('intval', array_pad(explode('.', $version), 3, '0'));

        return match ($part) {
            'major' => ($major + 1) . '.0.0',
            'minor' => $major . '.' . ($minor + 1) . '.0',
            default => $major . '.' . $minor . '.' . ($patch + 1),
        };
    }
}
