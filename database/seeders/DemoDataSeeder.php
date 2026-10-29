<?php

namespace Database\Seeders;

use App\Enums\DriverVerificationStatus;
use App\Enums\PageType;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use App\Models\DriverProfile;
use App\Models\Faq;
use App\Models\Page;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\OfferService;
use App\Services\OrderLifecycle;
use App\Services\ReviewService;
use Illuminate\Database\Seeder;

/**
 * Demo marketplace data for local development, testing and demos. All people
 * are fictional and every mobile number is a 0912000000x placeholder.
 */
class DemoDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(OrderLifecycle $lifecycle, OfferService $offers, ReviewService $reviews): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('freight.demo_seed')) {
            $this->command?->info('Skipping demo data (set DEMO_SEED=true to seed it).');

            return;
        }

        if (User::query()->where('mobile', '09120000001')->exists()) {
            $this->command?->info('Demo data already exists; skipping.');

            return;
        }

        if (City::query()->doesntExist()) {
            $this->call(ProvinceCitySeeder::class);
        }

        if (VehicleType::query()->doesntExist()) {
            $this->call(VehicleTypeSeeder::class);
        }

        $operator = $this->user('09120000007', 'اپراتور نمونه', UserRole::Operator);
        $editor = $this->user('09120000008', 'ویراستار نمونه', UserRole::ContentEditor);

        $sepehr = $this->user('09120000001', 'شرکت عمران سپهر', UserRole::Shipper);
        $ahmadi = $this->user('09120000002', 'فاطمه احمدی', UserRole::Shipper);
        $pars = $this->user('09120000003', 'بازرگانی پارس', UserRole::Shipper);
        $mehr = $this->user('09120000009', 'فروشگاه زنجیره‌ای مهر', UserRole::Shipper);

        $rezaei = $this->driver('09120000004', 'علی رضایی', 'curtain-trailer', 'tehran', 24000, DriverVerificationStatus::Approved);
        $karimi = $this->driver('09120000005', 'محمد کریمی', 'single-truck', 'isfahan', 10000, DriverVerificationStatus::Approved);
        $this->driver('09120000006', 'حسین امینی', 'nissan', 'karaj', 2000, DriverVerificationStatus::Pending);

        // Tehran to Isfahan: open for offers with two competing offers.
        $order = $lifecycle->submit($sepehr, $this->orderData('tehran', 'isfahan', 'مصالح ساختمانی', 8000, 'single-truck', 28500000, 5));
        $lifecycle->approve($order, $operator);
        $offers->submit($karimi, $order, 28000000, 'کامیون آماده بارگیری از صبح است.');
        $offers->submit($rezaei, $order, 29500000);

        // Karaj to Rasht: offer accepted, driver assigned.
        $order = $lifecycle->submit($ahmadi, $this->orderData('karaj', 'rasht', 'لوازم خانگی', 2000, 'khavar', 12800000, 4));
        $lifecycle->approve($order, $operator);
        $lifecycle->acceptOffer($offers->submit($karimi, $order, 12800000), $ahmadi);

        // Bandar Abbas to Tehran: in transit.
        $order = $lifecycle->submit($pars, $this->orderData('bandar-abbas', 'tehran', 'کالای تجاری', 18000, 'curtain-trailer', 64000000, 3));
        $lifecycle->approve($order, $operator);
        $lifecycle->acceptOffer($offers->submit($rezaei, $order, 64000000), $pars);
        $lifecycle->markLoaded($order, $rezaei);
        $lifecycle->markInTransit($order, $rezaei);

        // Mashhad to Neyshabur: delivered and reviewed with publish consent.
        $order = $lifecycle->submit($mehr, $this->orderData('mashhad', 'neyshabur', 'مواد غذایی', 1000, 'nissan', 5400000, 1));
        $lifecycle->approve($order, $operator);
        $lifecycle->acceptOffer($offers->submit($rezaei, $order, 5400000), $mehr);
        $lifecycle->markLoaded($order, $rezaei);
        $lifecycle->markInTransit($order, $rezaei);
        $lifecycle->markDelivered($order, $rezaei);
        $review = $reviews->create($mehr, $order, 5, 'پیگیری دقیق و تحویل بار در زمان توافق‌شده بود. راننده و پشتیبانی همکاری بسیار خوبی داشتند.', true);
        $reviews->moderate($review, $editor, ReviewStatus::Approved, 'از اعتماد شما سپاسگزاریم.', true);

        // Qom to Isfahan: waiting for operator review.
        $lifecycle->submit($pars, $this->orderData('qom', 'isfahan', 'قطعات صنعتی', 6000, 'khavar', 19000000, 6));

        $this->content($editor);
    }

    protected function user(string $mobile, string $name, UserRole $role): User
    {
        return User::create([
            'name' => $name,
            'mobile' => $mobile,
            'role' => $role,
            'is_active' => true,
            'password' => self::DEMO_PASSWORD,
        ]);
    }

    protected function driver(string $mobile, string $name, string $vehicle, string $city, int $capacity, DriverVerificationStatus $status): User
    {
        $user = $this->user($mobile, $name, UserRole::Driver);

        DriverProfile::create([
            'user_id' => $user->getKey(),
            'vehicle_type_id' => VehicleType::query()->where('slug', $vehicle)->value('id'),
            'plate_number' => '۱۱ع'.substr($mobile, -3).'-۱۱',
            'capacity_kg' => $capacity,
            'home_city_id' => City::query()->where('slug', $city)->value('id'),
            'verification_status' => $status,
            'verified_at' => $status === DriverVerificationStatus::Approved ? now() : null,
        ]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderData(string $origin, string $destination, string $cargo, int $weight, string $vehicle, int $price, int $days): array
    {
        return [
            'origin_city_id' => City::query()->where('slug', $origin)->value('id'),
            'destination_city_id' => City::query()->where('slug', $destination)->value('id'),
            'origin_address' => 'نشانی نمونه مبدأ',
            'destination_address' => 'نشانی نمونه مقصد',
            'cargo_type' => $cargo,
            'weight_kg' => $weight,
            'pickup_date' => now()->addDays($days)->toDateString(),
            'vehicle_type_id' => VehicleType::query()->where('slug', $vehicle)->value('id'),
            'offered_price' => $price,
        ];
    }

    protected function content(User $editor): void
    {
        $faqs = [
            'چطور سفارش حمل بار ثبت کنم؟' => 'پس از ورود با شماره موبایل، فرم ثبت سفارش را با مبدأ، مقصد، نوع بار و خودروی مورد نیاز تکمیل کنید. اپراتور سفارش را بررسی و برای رانندگان منتشر می‌کند.',
            'قیمت حمل چگونه تعیین می‌شود؟' => 'شما قیمت پیشنهادی خود را ثبت می‌کنید و رانندگان تأییدشده پیشنهاد قیمت می‌دهند. انتخاب نهایی با شماست.',
            'رانندگان چگونه تأیید می‌شوند؟' => 'مدارک هویتی و خودروی هر راننده پیش از فعال شدن حساب توسط تیم پشتیبانی بررسی می‌شود.',
            'تسویه حساب چگونه انجام می‌شود؟' => 'تسویه به صورت دستی و با هماهنگی پشتیبانی ثبت می‌شود و درگاه پرداخت آنلاین در سامانه وجود ندارد.',
        ];

        $sort = 1;

        foreach ($faqs as $question => $answer) {
            Faq::create([
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $sort++,
                'is_published' => true,
            ]);
        }

        $category = Category::create(['name' => 'راهنمای حمل بار', 'slug' => 'freight-guide']);

        Article::create([
            'category_id' => $category->getKey(),
            'title' => 'راهنمای انتخاب خودرو برای حمل بار تهران به اصفهان',
            'slug' => 'tehran-isfahan-vehicle-guide',
            'excerpt' => 'برای حمل بار در مسیر تهران به اصفهان، وزن و حجم بار تعیین می‌کند کدام خودرو مناسب‌تر است.',
            'body' => '<p>برای بارهای سبک تا دو تن نیسان یا وانت‌بار کافی است. برای مصالح ساختمانی تا ده تن کامیون تک و برای بارهای سنگین‌تر تریلی پیشنهاد می‌شود.</p>',
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'seo_title' => 'انتخاب خودرو برای حمل بار تهران به اصفهان',
            'seo_description' => 'راهنمای انتخاب نیسان، خاور، کامیون یا تریلی برای حمل بار در مسیر تهران به اصفهان.',
            'author_id' => $editor->getKey(),
        ]);

        $routes = [
            ['tehran', 'isfahan', 'حمل بار تهران به اصفهان'],
            ['bandar-abbas', 'tehran', 'حمل بار بندرعباس به تهران'],
            ['mashhad', 'neyshabur', 'حمل بار مشهد به نیشابور'],
        ];

        foreach ($routes as [$origin, $destination, $title]) {
            Page::create([
                'title' => $title,
                'slug' => $origin.'-to-'.$destination,
                'type' => PageType::Route,
                'origin_city_id' => City::query()->where('slug', $origin)->value('id'),
                'destination_city_id' => City::query()->where('slug', $destination)->value('id'),
                'body' => '<p>'.$title.' با رانندگان تأییدشده و پیگیری وضعیت سفارش تا لحظه تحویل.</p>',
                'status' => PublishStatus::Published,
                'seo_title' => $title,
                'seo_description' => $title.' با انواع خودرو از وانت‌بار تا تریلی.',
            ]);
        }
    }
}
