<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Navigation groups in the order they appear in the sidebar.
     */
    public const GROUP_ORDERS = 'سفارش‌ها';

    public const GROUP_FLEET = 'ناوگان';

    public const GROUP_CONTENT = 'محتوا';

    public const GROUP_FINANCE = 'مالی';

    public const GROUP_ADMIN = 'مدیریت';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName(config('app.name'))
            ->colors([
                'primary' => Color::Amber,
            ])
            // Vazirmatn is bundled by Vite (resources/css/filament-font.css).
            // LocalFontProvider renders no remote stylesheet link, so the
            // panel never requests a font from an external server.
            ->font('Vazirmatn', provider: LocalFontProvider::class)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): Htmlable => app(Vite::class)('resources/css/filament-font.css'),
            )
            // Avatars are generated locally instead of using an external
            // avatar image service.
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->navigationGroups([
                self::GROUP_ORDERS,
                self::GROUP_FLEET,
                self::GROUP_CONTENT,
                self::GROUP_FINANCE,
                self::GROUP_ADMIN,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
