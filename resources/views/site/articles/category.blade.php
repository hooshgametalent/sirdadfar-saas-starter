@extends('layouts.app', [
    'title' => $category->name,
    'description' => 'مقالات دسته '.$category->name.' در بارراه.',
    'canonical' => route('categories.show', $category->slug),
])

@section('content')
    <nav aria-label="مسیر صفحه" class="text-sm text-slate-500">
        <a href="{{ route('articles.index') }}" class="link">مقالات</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $category->name }}</span>
    </nav>

    <h1 class="mt-4 text-2xl font-bold">{{ $category->name }}</h1>

    @if ($articles->isEmpty())
        <p class="mt-6 text-slate-600">در این دسته هنوز مقاله‌ای منتشر نشده است.</p>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($articles as $article)
                @include('site.partials.article-card', ['article' => $article])
            @endforeach
        </div>

        <div class="mt-8">{{ $articles->links() }}</div>
    @endif
@endsection
