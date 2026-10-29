<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use DateTimeInterface;
use Illuminate\Http\Response;
use XMLWriter;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach (['home', 'articles.index', 'faq', 'testimonials', 'contact'] as $route) {
            $this->url($xml, route($route));
        }

        foreach (Article::query()->published()->orderBy('id')->get(['id', 'slug', 'updated_at']) as $article) {
            $this->url($xml, route('articles.show', $article->slug), $article->updated_at);
        }

        foreach (Page::query()->published()->orderBy('id')->get(['id', 'slug', 'updated_at']) as $page) {
            $this->url($xml, route('pages.show', $page->slug), $page->updated_at);
        }

        $categories = Category::query()
            ->whereHas('articles', fn ($query) => $query->published())
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at']);

        foreach ($categories as $category) {
            $this->url($xml, route('categories.show', $category->slug), $category->updated_at);
        }

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /shipper',
            'Disallow: /driver',
            'Disallow: /documents',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    protected function url(XMLWriter $xml, string $location, ?DateTimeInterface $lastModified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);

        if ($lastModified !== null) {
            $xml->writeElement('lastmod', $lastModified->format('Y-m-d'));
        }

        $xml->endElement();
    }
}
