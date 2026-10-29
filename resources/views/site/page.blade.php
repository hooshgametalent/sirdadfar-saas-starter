@extends('layouts.app', [
    'title' => $page->seo_title ?: $page->title,
    'description' => $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($page->body), 160),
    'canonical' => route('pages.show', $page->slug),
])

@section('content')
    <article class="mx-auto max-w-3xl">
        <p class="text-sm text-teal-700">{{ $page->type->label() }}</p>
        <h1 class="mt-2 text-3xl font-bold leading-tight">{{ $page->title }}</h1>

        @if ($page->type === \App\Enums\PageType::Route && $page->originCity && $page->destinationCity)
            <p class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm ring-1 ring-slate-200">
                <span>مبدأ: {{ $page->originCity->name }}</span>
                <span aria-hidden="true">←</span>
                <span>مقصد: {{ $page->destinationCity->name }}</span>
            </p>
        @endif

        <div class="prose-content mt-6">
            {!! \App\Support\Html::clean($page->body) !!}
        </div>

        @if ($page->type !== \App\Enums\PageType::StaticPage)
            <div class="card mt-10 flex flex-wrap items-center justify-between gap-4">
                <p class="font-bold">برای این مسیر بار دارید؟ درخواست خود را ثبت کنید.</p>
                <a href="{{ auth()->user()?->isShipper() ? route('shipper.orders.create') : route('register') }}" class="btn-primary">ثبت درخواست حمل</a>
            </div>
        @endif
    </article>
@endsection
