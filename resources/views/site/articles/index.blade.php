@extends('layouts.app', [
    'title' => 'مقالات و راهنمای حمل بار',
    'description' => 'راهنمای انتخاب خودرو، بسته‌بندی و ارسال بار جاده‌ای در مسیرهای مختلف ایران.',
    'canonical' => route('articles.index', $articles->currentPage() > 1 ? ['page' => $articles->currentPage()] : []),
])

@section('content')
    <h1 class="text-2xl font-bold">مقالات و راهنمای حمل بار</h1>

    @if ($articles->isEmpty())
        <p class="mt-6 text-slate-600">هنوز مقاله‌ای منتشر نشده است.</p>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($articles as $article)
                @include('site.partials.article-card', ['article' => $article])
            @endforeach
        </div>

        <div class="mt-8">{{ $articles->links() }}</div>
    @endif
@endsection
