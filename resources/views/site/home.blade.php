@extends('layouts.app', [
    'title' => 'ثبت و پیگیری حمل بار جاده‌ای',
    'description' => 'بارراه؛ ثبت درخواست حمل بار، دریافت پیشنهاد قیمت از رانندگان تأییدشده و پیگیری وضعیت بار تا لحظه تحویل در سراسر ایران.',
    'canonical' => route('home'),
])

@section('content')
    <div class="space-y-16">
        <section aria-labelledby="hero-heading" class="rounded-2xl bg-teal-800 px-6 py-12 text-white sm:px-10">
            <h1 id="hero-heading" class="text-3xl font-bold leading-tight sm:text-4xl">حمل بار مطمئن با رانندگان تأییدشده</h1>
            <p class="mt-4 max-w-2xl leading-8 text-teal-50">{{ $heroText }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="bg-white text-teal-900 hover:bg-teal-50 inline-flex items-center rounded-lg px-5 py-3 font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">ثبت درخواست حمل</a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg border border-white/60 px-5 py-3 font-bold text-white hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">عضویت رانندگان</a>
            </div>
        </section>

        <section aria-labelledby="how-heading">
            <h2 id="how-heading" class="text-2xl font-bold">بارراه چطور کار می‌کند؟</h2>
            <ol class="mt-6 grid gap-4 md:grid-cols-4">
                @php
                    $steps = [
                        ['ثبت درخواست', 'مبدأ، مقصد، نوع بار، وزن و قیمت پیشنهادی خود را ثبت کنید.'],
                        ['بررسی اپراتور', 'کارشناسان بارراه درخواست را بررسی و برای رانندگان واجد شرایط منتشر می‌کنند.'],
                        ['انتخاب پیشنهاد', 'پیشنهاد قیمت رانندگان تأییدشده را با امتیازشان مقایسه و یکی را انتخاب کنید.'],
                        ['پیگیری تا تحویل', 'وضعیت بارگیری، حرکت و تحویل را مرحله به مرحله دنبال کنید.'],
                    ];
                @endphp
                @foreach ($steps as $index => [$stepTitle, $stepText])
                    <li class="card">
                        <span aria-hidden="true" class="flex size-9 items-center justify-center rounded-full bg-teal-50 font-bold text-teal-800">{{ \App\Support\Jalali::toPersianDigits($index + 1) }}</span>
                        <h3 class="mt-3 font-bold">{{ $stepTitle }}</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $stepText }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($vehicleTypes->isNotEmpty())
            <section aria-labelledby="vehicles-heading">
                <h2 id="vehicles-heading" class="text-2xl font-bold">انواع خودروی حمل بار</h2>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($vehicleTypes as $vehicleType)
                        <li class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <strong class="block">{{ $vehicleType->name }}</strong>
                            @if ($vehicleType->capacity_kg)
                                <small class="text-slate-500">تا {{ \App\Support\Jalali::number($vehicleType->capacity_kg) }} کیلوگرم</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($routePages->isNotEmpty())
            <section aria-labelledby="routes-heading">
                <h2 id="routes-heading" class="text-2xl font-bold">مسیرهای پرتردد</h2>
                <ul class="mt-6 flex flex-wrap gap-3">
                    @foreach ($routePages as $routePage)
                        <li><a href="{{ route('pages.show', $routePage->slug) }}" class="btn-secondary">{{ $routePage->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($testimonials->isNotEmpty())
            <section aria-labelledby="testimonials-heading">
                <div class="flex items-center justify-between">
                    <h2 id="testimonials-heading" class="text-2xl font-bold">رضایت مشتریان</h2>
                    <a href="{{ route('testimonials') }}" class="link text-sm">همه نظرات</a>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($testimonials as $review)
                        @include('site.partials.review', ['review' => $review])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($articles->isNotEmpty())
            <section aria-labelledby="articles-heading">
                <div class="flex items-center justify-between">
                    <h2 id="articles-heading" class="text-2xl font-bold">تازه‌ترین مقالات</h2>
                    <a href="{{ route('articles.index') }}" class="link text-sm">همه مقالات</a>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($articles as $article)
                        @include('site.partials.article-card', ['article' => $article])
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="cta-heading" class="card flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 id="cta-heading" class="text-xl font-bold">آماده ارسال بار هستید؟</h2>
                <p class="mt-1 text-sm text-slate-600">ثبت درخواست رایگان است و انتخاب نهایی راننده با شماست.</p>
            </div>
            <a href="{{ route('register') }}" class="btn-primary">شروع کنید</a>
        </section>
    </div>
@endsection
