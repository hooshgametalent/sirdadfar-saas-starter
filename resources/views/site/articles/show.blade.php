@extends('layouts.app', [
    'title' => $article->seo_title ?: $article->title,
    'description' => $article->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?: $article->body), 160),
    'canonical' => route('articles.show', $article->slug),
    'ogType' => 'article',
])

@section('content')
    <article class="mx-auto max-w-3xl">
        <nav aria-label="مسیر صفحه" class="text-sm text-slate-500">
            <a href="{{ route('articles.index') }}" class="link">مقالات</a>
            @if ($article->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('categories.show', $article->category->slug) }}" class="link">{{ $article->category->name }}</a>
            @endif
        </nav>

        <h1 class="mt-4 text-3xl font-bold leading-tight">{{ $article->title }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ \App\Support\Jalali::date($article->published_at, 'j F Y') }}</time>
        </p>

        @if ($article->excerpt)
            <p class="mt-6 text-lg leading-8 text-slate-700">{{ $article->excerpt }}</p>
        @endif

        <div class="prose-content mt-6">
            {!! \App\Support\Html::clean($article->body) !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section aria-labelledby="related-heading" class="mx-auto mt-12 max-w-5xl">
            <h2 id="related-heading" class="text-xl font-bold">مقالات مرتبط</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($related as $item)
                    @include('site.partials.article-card', ['article' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
