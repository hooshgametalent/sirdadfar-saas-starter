<article class="card flex flex-col">
    @if ($article->category)
        <a href="{{ route('categories.show', $article->category->slug) }}" class="text-xs font-medium text-teal-700">{{ $article->category->name }}</a>
    @endif
    <h3 class="mt-2 text-lg font-bold">
        <a href="{{ route('articles.show', $article->slug) }}" class="hover:text-teal-800 focus-visible:outline-2 focus-visible:outline-teal-600">{{ $article->title }}</a>
    </h3>
    @if ($article->excerpt)
        <p class="mt-2 flex-1 text-sm leading-7 text-slate-600">{{ $article->excerpt }}</p>
    @endif
    <p class="mt-3 text-xs text-slate-500">{{ \App\Support\Jalali::date($article->published_at, 'j F Y') }}</p>
</article>
