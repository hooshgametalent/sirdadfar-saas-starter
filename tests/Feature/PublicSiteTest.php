<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Review;
use App\Models\User;
use App\Models\VehicleType;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_page_shows_vehicle_types_articles_and_only_consented_featured_reviews(): void
    {
        VehicleType::factory()->create(['name' => 'تریلی نمونه']);
        Article::factory()->published()->create(['title' => 'مقاله منتشرشده خانه']);
        Article::factory()->create(['title' => 'مقاله پیش نویس خانه']);

        $shipper = User::factory()->shipper()->create(['mobile' => '09001234567']);
        $featured = Review::factory()->approved()->create([
            'display_name' => 'زهرا م.',
            'body' => 'تحویل سریع و سالم بار',
            'is_featured' => true,
        ]);
        $featured->forceFill(['shipper_id' => $shipper->id])->save();
        Review::factory()->approved()->withoutConsent()->create(['body' => 'نظر بدون رضایت', 'is_featured' => true]);
        Review::factory()->create(['body' => 'نظر در انتظار بررسی', 'is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('تریلی نمونه')
            ->assertSee('مقاله منتشرشده خانه')
            ->assertDontSee('مقاله پیش نویس خانه')
            ->assertSee('زهرا م.')
            ->assertSee('تحویل سریع و سالم بار')
            ->assertDontSee('نظر بدون رضایت')
            ->assertDontSee('نظر در انتظار بررسی')
            ->assertDontSee('09001234567')
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('property="og:title"', false);
    }

    public function test_published_articles_render_with_seo_meta_and_drafts_return_404(): void
    {
        $category = Category::create(['name' => 'راهنمای حمل', 'slug' => 'freight-guide']);
        $article = Article::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'راهنمای انتخاب خودرو',
            'slug' => 'vehicle-guide',
            'seo_title' => 'عنوان سئو مقاله',
            'seo_description' => 'توضیح سئو مقاله',
            'body' => '<p>متن مقاله</p><script>alert(1)</script>',
        ]);
        $draft = Article::factory()->create(['title' => 'مقاله پیش نویس', 'slug' => 'draft-article']);
        $future = Article::factory()->create([
            'slug' => 'future-article',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('راهنمای انتخاب خودرو')
            ->assertDontSee('مقاله پیش نویس');

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<title>عنوان سئو مقاله | '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="توضیح سئو مقاله">', false)
            ->assertSee('<link rel="canonical" href="'.route('articles.show', 'vehicle-guide').'">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<p>متن مقاله</p>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get(route('articles.show', $draft->slug))->assertNotFound();
        $this->get(route('articles.show', $future->slug))->assertNotFound();

        $this->get(route('categories.show', 'freight-guide'))
            ->assertOk()
            ->assertSee('راهنمای انتخاب خودرو');
        $this->get(route('categories.show', 'missing-category'))->assertNotFound();
    }

    public function test_route_service_and_static_pages_render_and_drafts_return_404(): void
    {
        $route = Page::factory()->published()->create([
            'title' => 'حمل بار تهران به اصفهان',
            'slug' => 'tehran-to-isfahan',
            'type' => PageType::Route,
            'seo_title' => 'باربری تهران به اصفهان',
        ]);
        $static = Page::factory()->published()->create(['title' => 'درباره ما', 'slug' => 'about', 'type' => PageType::StaticPage]);
        $draft = Page::factory()->create(['slug' => 'draft-page']);

        $this->get(route('pages.show', $route->slug))
            ->assertOk()
            ->assertSee('حمل بار تهران به اصفهان')
            ->assertSee('<title>باربری تهران به اصفهان | '.config('app.name').'</title>', false)
            ->assertSee('ثبت درخواست حمل');

        $this->get(route('pages.show', $static->slug))->assertOk()->assertSee('درباره ما');
        $this->get(route('pages.show', $draft->slug))->assertNotFound();
    }

    public function test_faq_shows_only_published_questions(): void
    {
        Faq::create(['question' => 'پرسش منتشرشده', 'answer' => 'پاسخ نمونه', 'sort_order' => 1, 'is_published' => true]);
        Faq::create(['question' => 'پرسش پنهان', 'answer' => 'پاسخ پنهان', 'sort_order' => 2, 'is_published' => false]);

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('پرسش منتشرشده')
            ->assertDontSee('پرسش پنهان');
    }

    public function test_testimonials_never_show_unconsented_reviews_or_mobiles(): void
    {
        $shipper = User::factory()->shipper()->create(['name' => 'زهرا محمدی', 'mobile' => '09007654321']);

        $published = Review::factory()->approved()->create(['display_name' => 'زهرا م.', 'body' => 'نظر منتشرشده', 'admin_reply' => 'از شما سپاسگزاریم']);
        $published->forceFill(['shipper_id' => $shipper->id])->save();
        Review::factory()->approved()->withoutConsent()->create(['body' => 'نظر بدون رضایت انتشار']);
        Review::factory()->create(['body' => 'نظر تأیید نشده']);

        $this->get(route('testimonials'))
            ->assertOk()
            ->assertSee('زهرا م.')
            ->assertSee('نظر منتشرشده')
            ->assertSee('از شما سپاسگزاریم')
            ->assertDontSee('نظر بدون رضایت انتشار')
            ->assertDontSee('نظر تأیید نشده')
            ->assertDontSee('زهرا محمدی')
            ->assertDontSee('09007654321');
    }

    public function test_contact_page_uses_settings(): void
    {
        $this->get(route('contact'))->assertOk()->assertSee('تماس با ما');

        Settings::set('support_phone', '021-00000000');

        $this->get(route('contact'))->assertOk()->assertSee('021-00000000');
    }

    public function test_sitemap_lists_only_published_content(): void
    {
        Article::factory()->published()->create(['slug' => 'published-article']);
        Article::factory()->create(['slug' => 'draft-article']);
        Page::factory()->published()->create(['slug' => 'published-page']);
        Page::factory()->create(['slug' => 'draft-page']);

        $response = $this->get(route('sitemap'))->assertOk();

        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee(route('articles.show', 'published-article'), false)
            ->assertSee(route('pages.show', 'published-page'), false)
            ->assertSee(route('home'), false)
            ->assertDontSee('draft-article')
            ->assertDontSee('draft-page');
    }

    public function test_robots_txt_references_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'), false)
            ->assertSee('Disallow: /admin', false);
    }
}
