<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Store' }} — Shadow Haven Hosting</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 antialiased">
    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 border-b border-gray-800/60 bg-gray-950/80 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('shh-store.store') }}" class="flex items-center gap-3" wire:navigate>
                <span class="text-xl font-bold tracking-tight text-white">
                    <span class="text-indigo-400">SH</span> Shadow Haven Hosting
                </span>
            </a>

            <div class="flex items-center gap-6">
                <a href="{{ route('shh-store.store') }}" class="text-sm font-medium text-gray-300 transition hover:text-white" wire:navigate>
                    Game Servers
                </a>
                <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" class="text-sm font-medium text-gray-300 transition hover:text-white">
                    Discord
                </a>
            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-800/60 bg-gray-950 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div>
                    <p class="text-sm font-semibold text-white">Shadow Haven Hosting</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Servers in partnership with Thunder Buddies Studio
                    </p>
                </div>
                <div class="flex items-center gap-6">
                    <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" class="text-sm text-gray-400 transition hover:text-indigo-400">
                        Join Discord
                    </a>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-800/60 pt-8 text-center">
                <p class="text-xs text-gray-600">&copy; {{ date('Y') }} Shadow Haven Hosting. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
