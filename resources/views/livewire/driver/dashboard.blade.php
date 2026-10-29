<div class="space-y-8">
    <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">سلام {{ $user->name }}، سفر خوبی داشته باشید</h2>
            <p class="mt-1 text-sm text-slate-600">بارهای آماده حمل، پیشنهادها و سفرهای خود را از اینجا دنبال کنید.</p>
        </div>
        @if ($isVerified)
            <a href="{{ route('driver.orders.open') }}" class="btn-primary">مشاهده بارهای آماده حمل</a>
        @endif
    </section>

    <section class="card" aria-labelledby="verification-heading">
        <h2 id="verification-heading" class="text-lg font-bold">وضعیت تأیید حساب</h2>
        @if ($profile === null)
            <p class="mt-2 text-sm text-slate-700">برای دریافت بار، ابتدا اطلاعات خودرو و مدارک خود را ثبت کنید.</p>
            <a href="{{ route('driver.profile') }}" class="btn-primary mt-4">تکمیل پروفایل</a>
        @else
            <p class="mt-2 flex items-center gap-2 text-sm">
                <x-status-pill :status="$profile->verification_status" />
                <span>{{ $profile->vehicleType?->name }} · پلاک {{ $profile->plate_number }}</span>
            </p>
            @if ($profile->verification_status === \App\Enums\DriverVerificationStatus::Rejected && $profile->rejection_reason)
                <p class="mt-2 text-sm text-rose-800">دلیل رد: {{ $profile->rejection_reason }}</p>
            @endif
            @unless ($isVerified)
                <p class="mt-2 text-sm text-slate-600">پس از بررسی مدارک توسط پشتیبانی، امکان مشاهده بارها و ثبت پیشنهاد فعال می‌شود.</p>
                <a href="{{ route('driver.profile') }}" class="link mt-2 inline-block text-sm">مدیریت پروفایل و مدارک</a>
            @endunless
        @endif
    </section>

    <section aria-label="آمار راننده" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="بارهای آماده حمل" :value="\App\Support\Jalali::number($openOrders)" icon="▤" tone="sky" />
        <x-metric-card label="پیشنهادهای در انتظار" :value="\App\Support\Jalali::number($pendingOffers)" icon="↯" tone="amber" />
        <x-metric-card label="سفرهای فعال" :value="\App\Support\Jalali::number($activeJobs)" icon="⇄" tone="violet" :href="route('driver.jobs')" />
        <x-metric-card label="سفرهای تحویل شده" :value="\App\Support\Jalali::number($deliveredJobs)" icon="✓" tone="emerald" />
    </section>
</div>
