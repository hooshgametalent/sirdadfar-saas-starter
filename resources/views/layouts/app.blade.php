<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) && filled((string) $title) ? $title.' | '.config('app.name') : config('app.name') }}</title>
        <meta name="description" content="{{ $description ?? 'پلتفرم مدیریت حمل‌ونقل جاده‌ای بار در ایران' }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
            رفتن به محتوای اصلی
        </a>

        <header class="border-b border-slate-200 bg-white">
            <nav aria-label="ناوبری اصلی" class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <a href="{{ url('/') }}" class="text-lg font-bold text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                    {{ config('app.name') }}
                </a>
                @isset($navigation)
                    {{ $navigation }}
                @endisset
            </nav>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 py-8">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-600">
                &copy; {{ config('app.name') }}
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
