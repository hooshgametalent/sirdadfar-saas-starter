<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('site.articles.index', [
            'articles' => Article::query()->published()->with('category')->latest('published_at')->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()->published()->with('category')->where('slug', $slug)->firstOrFail();

        return view('site.articles.show', [
            'article' => $article,
            'related' => Article::query()
                ->published()
                ->whereKeyNot($article->getKey())
                ->when($article->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    public function category(string $slug): View
    {
        $category = Category::query()->where('slug', $slug)->firstOrFail();

        return view('site.articles.category', [
            'category' => $category,
            'articles' => $category->articles()->published()->latest('published_at')->paginate(9),
        ]);
    }
}
