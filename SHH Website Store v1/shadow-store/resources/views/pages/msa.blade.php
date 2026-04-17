<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $msaPage['title'] }} - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #050816; }

        .editor-content > * + * {
            margin-top: 1rem;
        }

        .editor-content h1,
        .editor-content h2,
        .editor-content h3,
        .editor-content h4 {
            color: #fff;
        }

        .editor-content a {
            color: #6ee7b7;
        }

        .editor-content img {
            border-radius: 1rem;
        }
    </style>
</head>
<body class="text-white min-h-screen bg-gradient-to-b from-slate-950 via-blue-950/40 to-slate-950">

    <header class="border-b border-slate-800/80 bg-slate-950/90 backdrop-blur sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ $storeHeader['store_url'] }}" class="flex items-center gap-3">
                @include('shadow-store::pages.partials.store-logo', [
                    'sizeClass' => 'h-9 w-9',
                    'containerClass' => 'rounded-lg bg-emerald-500 p-1',
                    'imageClass' => 'h-full w-full object-contain',
                    'textClass' => 'font-black text-black',
                ])
                <div>
                    <div class="font-semibold">{{ $storeHeader['brand_name'] }}</div>
                    <div class="text-[10px] uppercase tracking-[0.18em] text-slate-500">{{ $storeHeader['brand_tagline'] }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-5 text-sm">
                <a href="{{ $storeHeader['store_url'] }}" class="text-slate-300 hover:text-white">{{ $storeHeader['store_label'] }}</a>
                @auth
                    <a href="/" class="text-slate-300 hover:text-white">My Servers</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10">
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-slate-500">
                <li><a href="/store" class="hover:text-white">Store</a></li>
                <li>/</li>
                <li class="text-slate-300">{{ $msaPage['title'] }}</li>
            </ol>
        </nav>

        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-8 md:p-10 text-slate-200">
            @if(filled($msaPage['content']))
                <div class="editor-content">{!! $msaPage['content'] !!}</div>
            @else
                @include('shadow-store::partials.msa-content')
            @endif
        </div>

        <div class="mt-8 text-center">
            <a href="/store" class="inline-block bg-emerald-500 hover:bg-emerald-400 text-black font-bold px-8 py-3 rounded-xl transition">
                Back to Store
            </a>
        </div>
    </main>

    <footer class="py-10 px-6 border-t border-slate-800/80 mt-16">
        <div class="max-w-5xl mx-auto text-center text-sm text-slate-500">
            Copyright {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
        </div>
    </footer>

</body>
</html>
