<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Version Card --}}
        <x-filament::section>
            <x-slot name="heading">Plugin Version</x-slot>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Installed Version</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $currentVersion ?? 'Unknown' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Latest Version</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $latestVersion ?? 'Unknown' }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Status Card --}}
        <x-filament::section>
            <x-slot name="heading">Update Status</x-slot>

            @if($error)
                <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 dark:border-danger-600 dark:bg-danger-500/10">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-500" />
                        <p class="text-sm font-medium text-danger-700 dark:text-danger-400">{{ $error }}</p>
                    </div>
                </div>
            @elseif($updateAvailable)
                <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-600 dark:bg-warning-500/10">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-arrow-up-circle class="h-5 w-5 text-warning-500" />
                        <div>
                            <p class="text-sm font-medium text-warning-700 dark:text-warning-400">
                                Update available: v{{ $latestVersion }}
                            </p>
                            <p class="mt-1 text-xs text-warning-600 dark:text-warning-500">
                                You are on v{{ $currentVersion }}. Download the latest release from the repository and replace the plugin files.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">How to update:</p>
                    <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        <li>Download the latest release from <a href="https://github.com/FlannelDev/SSH-Store" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 hover:underline dark:text-primary-400">GitHub</a></li>
                        <li>Replace the <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">plugins/shh-store</code> directory with the new files</li>
                        <li>Run <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">php artisan migrate</code> if the update includes database changes</li>
                        <li>Clear caches: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">php artisan optimize:clear</code></li>
                    </ol>
                </div>
            @else
                <div class="rounded-lg border border-success-300 bg-success-50 p-4 dark:border-success-600 dark:bg-success-500/10">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-check-circle class="h-5 w-5 text-success-500" />
                        <p class="text-sm font-medium text-success-700 dark:text-success-400">
                            You are running the latest version (v{{ $currentVersion }}).
                        </p>
                    </div>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
