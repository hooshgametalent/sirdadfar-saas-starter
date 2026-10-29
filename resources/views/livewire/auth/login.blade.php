<div class="mx-auto max-w-md">
    <section class="card" aria-labelledby="login-heading">
        <h1 id="login-heading" class="text-2xl font-bold">ورود به حساب</h1>
        <p class="mt-2 text-sm text-slate-600">با شماره موبایل و رمز عبور خود وارد شوید.</p>

        <form wire:submit="login" class="mt-6 space-y-4" novalidate>
            <x-form-field label="شماره موبایل" for="mobile" required>
                <input id="mobile" type="tel" inputmode="numeric" autocomplete="username" dir="ltr"
                       wire:model="mobile" class="form-input text-start" placeholder="09xxxxxxxxx" required
                       @error('mobile') aria-invalid="true" aria-describedby="mobile-error" @enderror>
            </x-form-field>

            <x-form-field label="رمز عبور" for="password" required>
                <input id="password" type="password" autocomplete="current-password" dir="ltr"
                       wire:model="password" class="form-input" required
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            </x-form-field>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                مرا به خاطر بسپار
            </label>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">ورود</span>
                <span wire:loading wire:target="login">در حال بررسی...</span>
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-600">
            حساب کاربری ندارید؟
            <a href="{{ route('register') }}" class="link">ثبت‌نام صاحب بار یا راننده</a>
        </p>
    </section>
</div>
