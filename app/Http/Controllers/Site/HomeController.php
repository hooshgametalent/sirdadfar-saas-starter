<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\Review;
use App\Models\VehicleType;
use App\Support\Settings;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'heroText' => Settings::get('hero_text', 'بار خود را ثبت کنید، پیشنهاد رانندگان تأییدشده را ببینید و تا لحظه تحویل وضعیت حمل را دنبال کنید.'),
            'vehicleTypes' => VehicleType::query()->active()->get(['id', 'name', 'capacity_kg']),
            'testimonials' => Review::query()
                ->publishable()
                ->where('is_featured', true)
                ->latest('moderated_at')
                ->limit(6)
                ->get(['id', 'display_name', 'rating', 'body', 'admin_reply']),
            'articles' => Article::query()->published()->latest('published_at')->limit(3)->get(),
            'routePages' => Page::query()->published()->ofType(PageType::Route)->orderBy('title')->limit(6)->get(['id', 'title', 'slug']),
        ]);
    }
}
