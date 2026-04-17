<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedicated Servers - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://inventory-widget.reliablesite.net/rs-inventory.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        .editor-content > * + * {
            margin-top: 1rem;
        }

        .editor-content a {
            color: #93c5fd;
        }

        .editor-content img {
            border-radius: 1rem;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <header class="border-b border-gray-800 sticky top-0 bg-gray-900/95 backdrop-blur z-50">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ $storeHeader['store_url'] }}" class="flex items-center gap-3">
                @include('shadow-store::pages.partials.store-logo', [
                    'sizeClass' => 'h-10 w-10',
                    'containerClass' => 'rounded-lg bg-blue-600 p-1',
                    'imageClass' => 'h-full w-full object-contain',
                    'textClass' => 'font-bold text-lg text-white',
                ])
                <div>
                    <div class="font-semibold text-lg">{{ $storeHeader['brand_name'] }}</div>
                    <div class="text-xs uppercase tracking-[0.18em] text-gray-500">{{ $storeHeader['brand_tagline'] }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-6">
                <a href="{{ $storeHeader['store_url'] }}" class="text-gray-300 hover:text-white">{{ $storeHeader['store_label'] }}</a>
                <a href="{{ $storeHeader['dedicated_url'] }}" class="text-white font-medium">{{ $storeHeader['dedicated_label'] }}</a>
                <a href="{{ $storeHeader['wiki_url'] }}" class="text-gray-300 hover:text-white">{{ $storeHeader['wiki_label'] }}</a>
                @auth
                    <a href="/" class="text-gray-300 hover:text-white">My Servers</a>
                @else
                    <a href="/login" class="text-gray-300 hover:text-white">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <div class="text-center mb-12 max-w-3xl mx-auto">
            <h1 class="text-4xl font-bold mb-4">{{ $dedicatedPage['title'] }}</h1>
            <p class="text-gray-400 text-lg">
                {{ $dedicatedPage['subtitle'] }}
            </p>
        </div>

        @if(!empty($dedicatedPage['media']))
            <div class="max-w-5xl mx-auto mb-10 overflow-hidden rounded-2xl border border-gray-800 shadow-2xl">
                <img src="{{ $dedicatedPage['media']['url'] }}" alt="{{ $dedicatedPage['media']['alt_text'] ?: $dedicatedPage['media']['name'] }}" class="h-72 w-full object-cover">
            </div>
        @endif

        @if(filled($dedicatedPage['content']))
            <div class="max-w-5xl mx-auto mb-10 rounded-2xl border border-gray-800 bg-gray-950/70 p-8 text-left text-gray-200 shadow-xl">
                <div class="editor-content max-w-none leading-8">{!! $dedicatedPage['content'] !!}</div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto bg-white rounded-2xl overflow-hidden shadow-2xl">
            <rs-inventory
                data-manifest-url="https://inventory-widget.reliablesite.net/latest.json"
                data-provider="static"
                data-base-url="https://payments.reliablesite.net"
                data-theme="gaming"
                data-default-color-mode="dark"
                data-show-theme-toggle="true"
                data-font-size="15px"
                data-button-text="Buy now"
                data-margin-type="percentage"
                data-margin-value="25"
            ></rs-inventory>
        </div>
    </main>

    <footer class="py-12 px-6 border-t border-gray-800 mt-12">
        <div class="container mx-auto text-center text-sm text-gray-500">
            © {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
        </div>
    </footer>
</body>
</html>
