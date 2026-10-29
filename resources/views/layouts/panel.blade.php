@php
    $user = auth()->user();
    $navItems = match (true) {
        $user?->isShipper() === true => [
            ['route' => 'shipper.dashboard', 'label' => 'داشبورد', 'icon' => '▦'],
            ['route' => 'shipper.orders.create', 'label' => 'ثبت درخواست حمل', 'icon' => '+'],
            ['route' => 'shipper.orders.index', 'label' => 'سفارش‌های من', 'icon' => '▤', 'pattern' => 'shipper.orders.index,shipper.orders.show'],
        ],
        $user?->isDriver() === true => [
            ['route' => 'driver.dashboard', 'label' => 'داشبورد', 'icon' => '▦'],
            ['route' => 'driver.profile', 'label' => 'پروفایل و مدارک', 'icon' => '♧'],
            ['route' => 'driver.orders.open', 'label' => 'بارهای آماده حمل', 'icon' => '▤'],
            ['route' => 'driver.jobs', 'label' => 'سفرهای من', 'icon' => '⇄'],
        ],
        default => [],
    };
    $pageTitle = isset($title) && filled((string) $title) ? $title.' | '.config('app.name') : config('app.name');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $pageTitle }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
            رفتن به محتوای اصلی
        </a>

        <div class="lg:flex">
            <aside class="border-b border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-72 lg:flex-col lg:border-b-0 lg:border-e">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-6 py-5 focus-visible:outline-2 focus-visible:outline-teal-600">
                    <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-xl bg-teal-700 text-xl font-bold text-white">ب</span>
                    <span>
                        <strong class="block text-lg">{{ config('app.name') }}</strong>
                        <small class="text-slate-500">مدیریت هوشمند حمل بار</small>
                    </span>
                </a>

                <nav aria-label="ناوبری پنل" class="px-3 pb-4 lg:flex-1">
                    <ul class="flex flex-wrap gap-1 lg:flex-col">
                        @foreach ($navItems as $item)
                            @php($active = request()->routeIs(...explode(',', $item['pattern'] ?? $item['route'])))
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'flex items-center gap-3 rounded-lg px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-teal-600',
                                       'bg-teal-50 font-bold text-teal-800' => $active,
                                       'text-slate-700 hover:bg-slate-100' => ! $active,
                                   ])
                                   @if ($active) aria-current="page" @endif>
                                    <span aria-hidden="true" class="w-5 text-center">{{ $item['icon'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="hidden px-6 pb-4 lg:block">
                    <div class="rounded-xl bg-slate-50 p-4 text-sm">
                        <strong class="block">نیاز به پشتیبانی دارید؟</strong>
                        <small class="text-slate-600">کارشناسان بارراه پاسخگوی شما هستند.</small>
                        <a href="{{ route('contact') }}" class="link mt-2 block">راه‌های تماس</a>
                    </div>
                </div>

                @if ($user)
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span aria-hidden="true" class="flex size-9 items-center justify-center rounded-full bg-slate-200 font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                            <span class="text-sm">
                                <strong class="block">{{ $user->name }}</strong>
                                <small class="text-slate-500">{{ $user->role->label() }}</small>
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">خروج</button>
                        </form>
                    </div>
                @endif
            </aside>

            <main id="main-content" tabindex="-1" class="flex-1 px-4 py-8 lg:px-10">
                @isset($title)
                    <h1 class="mb-6 text-2xl font-bold">{{ $title }}</h1>
                @endisset

                <x-flash />

                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
