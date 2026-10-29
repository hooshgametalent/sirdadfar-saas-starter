<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Review;
use App\Support\Settings;
use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    /**
     * Route, service and static pages managed in the admin panel.
     */
    public function page(string $slug): View
    {
        $page = Page::query()
            ->published()
            ->with(['originCity', 'destinationCity'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('site.page', ['page' => $page]);
    }

    public function faq(): View
    {
        return view('site.faq', [
            'faqs' => Faq::query()->published()->get(['id', 'question', 'answer']),
        ]);
    }

    /**
     * Only approved reviews with explicit publish consent are listed, and
     * only the anonymised display name is selected, never shipper data.
     */
    public function testimonials(): View
    {
        return view('site.testimonials', [
            'reviews' => Review::query()
                ->publishable()
                ->latest('moderated_at')
                ->paginate(12, ['id', 'display_name', 'rating', 'body', 'admin_reply', 'moderated_at']),
        ]);
    }

    public function contact(): View
    {
        return view('site.contact', [
            'phone' => Settings::get('support_phone'),
            'email' => Settings::get('support_email'),
            'address' => Settings::get('address'),
        ]);
    }
}
