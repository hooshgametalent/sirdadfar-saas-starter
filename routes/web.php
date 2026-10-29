<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DriverDocumentController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\SitemapController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Driver\Dashboard as DriverDashboard;
use App\Livewire\Driver\MyJobs;
use App\Livewire\Driver\OpenOrders;
use App\Livewire\Driver\Profile as DriverProfile;
use App\Livewire\Shipper\CreateOrder;
use App\Livewire\Shipper\Dashboard as ShipperDashboard;
use App\Livewire\Shipper\MyOrders;
use App\Livewire\Shipper\OrderShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/categories/{slug}', [ArticleController::class, 'category'])->name('categories.show');
Route::get('/pages/{slug}', [SiteController::class, 'page'])->name('pages.show');
Route::get('/faq', [SiteController::class, 'faq'])->name('faq');
Route::get('/testimonials', [SiteController::class, 'testimonials'])->name('testimonials');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentication (mobile + password)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/documents/{document}', [DriverDocumentController::class, 'show'])->name('documents.show');
});

/*
|--------------------------------------------------------------------------
| Shipper panel
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:shipper'])->prefix('shipper')->name('shipper.')->group(function () {
    Route::livewire('/', ShipperDashboard::class)->name('dashboard');
    Route::livewire('/orders', MyOrders::class)->name('orders.index');
    Route::livewire('/orders/create', CreateOrder::class)->name('orders.create');
    Route::livewire('/orders/{order}', OrderShow::class)->whereNumber('order')->name('orders.show');
});

/*
|--------------------------------------------------------------------------
| Driver panel
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::livewire('/', DriverDashboard::class)->name('dashboard');
    Route::livewire('/profile', DriverProfile::class)->name('profile');
    Route::livewire('/orders', OpenOrders::class)->name('orders.open');
    Route::livewire('/jobs', MyJobs::class)->name('jobs');
});
