---
inclusion: always
---

# Freight platform conventions

- Stack: Laravel 13 (PHP 8.3+), Livewire 4 for the public site and the shipper and driver panels, Filament 5 for the admin panel at /admin, MySQL 8.4, Tailwind CSS 4 through Vite.
- Self-hosted only: no CDN, remote fonts, map tiles, avatar services or SMS/payment provider calls at runtime. The Vazirmatn font is bundled from npm. New providers go behind an interface (for example App\Contracts\SmsSender) with a local default.
- Persian RTL UI (`lang="fa" dir="rtl"`), Jalali dates and Persian digits through App\Support\Jalali. Persian docs, no em dashes.
- Authentication is mobile + password. Roles come from App\Enums\UserRole; access is enforced by policies and the `role` middleware, never only in the UI.
- Every order status change goes through App\Services\OrderLifecycle (DB transaction, row lock, status history and audit log).
- Queue, cache and session use the database driver. Driver documents live on the private local disk and are served only through an authorized controller.
- Production runs from the Dockerfile (nginx, PHP-FPM, queue worker and scheduler under supervisor) with docker-compose.yml (app + MySQL). See docs/DEPLOYMENT.fa.md.
- Before committing: `vendor/bin/pint --test` and `php artisan test`.
