@php
    $pageTitle = isset($title) && filled((string) $title) ? $title.' | '.config('app.name') : config('app.name').' | سامانه حمل‌ونقل جاده‌ای بار';
    $pageDescription = isset($description) && filled((string) $description) ? (string) $description : 'بارراه؛ ثبت درخواست حمل بار، دریافت پیشنهاد قیمت از رانندگان تأییدشده و پیگیری بار تا لحظه تحویل.';
    $canonicalUrl = isset($canonical) && filled((string) $canonical) ? (string) $canonical : url()->current();
    $navItems = [
        ['route' => 'home', 'label' => 'خانه'],
        ['route' => 'articles.index', 'label' => 'مقالات'],
        ['route' => 'testimonials', 'label' => 'نظرات مشتریان'],
        ['route' => 'faq', 'label' => 'سوالات متداول'],
        ['route' => 'contact', 'label' => 'تماس با ما'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <meta property="og:locale" content="fa_IR">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:type" content="{{ $ogType ?? 'website' }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
            رفتن به محتوای اصلی
        </a>

        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">
                    <span aria-hidden="true" class="flex size-9 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white">ب</span>
                    <span class="text-lg font-bold text-slate-900">{{ config('app.name') }}</span>
                </a>

                <nav aria-label="ناوبری اصلی">
                    <ul class="flex flex-wrap items-center gap-1 text-sm">
                        @foreach ($navItems as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'rounded-md px-3 py-2 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-teal-600',
                                       'font-bold text-teal-800' => request()->routeIs($item['route']),
                                       'text-slate-700' => ! request()->routeIs($item['route']),
                                   ])
                                   @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="flex items-center gap-2 text-sm">
                    @auth
                        <a href="{{ auth()->user()->homeUrl() }}" class="btn-primary">پنل کاربری</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">خروج</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary">ورود</a>
                        <a href="{{ route('register') }}" class="btn-primary">ثبت‌نام</a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            <x-flash />

            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm text-slate-600">
                <p>&copy; {{ config('app.name') }}؛ مدیریت هوشمند حمل بار</p>
                <nav aria-label="پیوندهای پایین صفحه">
                    <ul class="flex flex-wrap gap-4">
                        <li><a class="link" href="{{ route('faq') }}">سوالات متداول</a></li>
                        <li><a class="link" href="{{ route('contact') }}">تماس با ما</a></li>
                        <li><a class="link" href="{{ route('register') }}">ثبت‌نام رانندگان</a></li>
                    </ul>
                </nav>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
