<div class="space-y-8">
    <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">سلام {{ $user->name }}، روز خوبی داشته باشید</h2>
            <p class="mt-1 text-sm text-slate-600">در یک نگاه، وضعیت سفارش‌های حمل خود را دنبال کنید.</p>
        </div>
        <a href="{{ route('shipper.orders.create') }}" class="btn-primary">ثبت درخواست جدید</a>
    </section>

    <section aria-label="آمار سفارش‌ها" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="همه سفارش‌ها" :value="\App\Support\Jalali::number($total)" icon="▤" />
        <x-metric-card label="در انتظار بررسی" :value="\App\Support\Jalali::number($pendingReview)" icon="…" tone="amber" />
        <x-metric-card label="دریافت پیشنهاد" :value="\App\Support\Jalali::number($openForOffers)" icon="↯" tone="sky" />
        <x-metric-card label="در حال حمل" :value="\App\Support\Jalali::number($inProgress)" hint="تخصیص یافته، بارگیری شده یا در مسیر" icon="⇄" tone="violet" />
        <x-metric-card label="تحویل شده" :value="\App\Support\Jalali::number($delivered)" icon="✓" tone="emerald" />
    </section>

    <section class="card" aria-labelledby="recent-orders-heading">
        <div class="flex items-center justify-between">
            <h2 id="recent-orders-heading" class="text-lg font-bold">آخرین سفارش‌ها</h2>
            <a href="{{ route('shipper.orders.index') }}" class="link text-sm">همه سفارش‌ها</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="mt-4 text-sm text-slate-600">هنوز سفارشی ثبت نکرده‌اید. اولین درخواست حمل خود را ثبت کنید.</p>
        @else
            @include('livewire.shipper.partials.orders-table', ['orders' => $recentOrders])
        @endif
    </section>
</div>
