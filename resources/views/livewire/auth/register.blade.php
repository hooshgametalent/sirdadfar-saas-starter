<div class="mx-auto max-w-md">
    <section class="card" aria-labelledby="register-heading">
        <h1 id="register-heading" class="text-2xl font-bold">ثبت‌نام در بارراه</h1>
        <p class="mt-2 text-sm text-slate-600">نوع حساب خود را انتخاب کنید. حساب رانندگان پس از بررسی مدارک فعال می‌شود.</p>

        <form wire:submit="register" class="mt-6 space-y-4" novalidate>
            <fieldset>
                <legend class="text-sm font-medium text-slate-800">نوع حساب</legend>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 p-3 text-sm has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                        <input type="radio" name="role" value="shipper" wire:model="role" class="text-teal-700 focus:ring-teal-600">
                        صاحب بار
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 p-3 text-sm has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                        <input type="radio" name="role" value="driver" wire:model="role" class="text-teal-700 focus:ring-teal-600">
                        راننده
                    </label>
                </div>
                @error('role')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-form-field label="نام و نام خانوادگی یا نام شرکت" for="name" required>
                <input id="name" type="text" autocomplete="name" wire:model="name" class="form-input" required
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            </x-form-field>

            <x-form-field label="شماره موبایل" for="mobile" hint="مثلاً 09121234567" required>
                <input id="mobile" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr"
                       wire:model="mobile" class="form-input text-start" required aria-describedby="mobile-hint @error('mobile') mobile-error @enderror"
                       @error('mobile') aria-invalid="true" @enderror>
            </x-form-field>

            <x-form-field label="رمز عبور" for="password" hint="حداقل ۸ کاراکتر" required>
                <input id="password" type="password" autocomplete="new-password" dir="ltr"
                       wire:model="password" class="form-input" required aria-describedby="password-hint @error('password') password-error @enderror"
                       @error('password') aria-invalid="true" @enderror>
            </x-form-field>

            <x-form-field label="تکرار رمز عبور" for="password_confirmation" required>
                <input id="password_confirmation" type="password" autocomplete="new-password" dir="ltr"
                       wire:model="password_confirmation" class="form-input" required>
            </x-form-field>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">ایجاد حساب</button>
        </form>

        <p class="mt-6 text-sm text-slate-600">
            قبلاً ثبت‌نام کرده‌اید؟
            <a href="{{ route('login') }}" class="link">ورود به حساب</a>
        </p>
    </section>
</div>
