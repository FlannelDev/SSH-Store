<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Store' }} — Shadow Haven Hosting</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-950 font-sans text-gray-200 antialiased">
    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 border-b border-white/5 bg-gray-950/90 backdrop-blur-sm">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('shh-store.store') }}" class="text-sm font-semibold tracking-tight text-white" wire:navigate>
                Shadow Haven Hosting
            </a>

            <div class="flex items-center gap-5">
                <a href="{{ route('shh-store.store') }}" class="text-sm text-gray-400 transition hover:text-white" wire:navigate>
                    Store
                </a>
                <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" rel="noopener noreferrer" class="text-sm text-gray-400 transition hover:text-white">
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
    <footer class="border-t border-white/5 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                <div>
                    <p class="text-sm font-medium text-gray-300">Shadow Haven Hosting</p>
                    <p class="mt-0.5 text-xs text-gray-600">In partnership with Thunder Buddies Studio</p>
                </div>
                <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" rel="noopener noreferrer" class="text-xs text-gray-500 transition hover:text-gray-300">
                    Join Discord
                </a>
            </div>
            <p class="mt-6 text-center text-xs text-gray-700">&copy; {{ date('Y') }} Shadow Haven Hosting. All rights reserved.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
