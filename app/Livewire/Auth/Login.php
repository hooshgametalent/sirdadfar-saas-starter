<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\Jalali;
use App\Support\Mobile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Mobile + password sign in. SMS one time passwords are not implemented;
 * App\Contracts\SmsSender can be used to add an OTP step later.
 */
#[Layout('layouts.app')]
#[Title('ورود به حساب')]
class Login extends Component
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public string $mobile = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->mobile = Mobile::normalize($this->mobile);

        $this->validate([
            'mobile' => ['required', 'regex:'.Mobile::PATTERN],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'mobile' => 'تعداد تلاش‌های ناموفق زیاد است. لطفاً '.Jalali::toPersianDigits($seconds).' ثانیه دیگر دوباره تلاش کنید.',
            ]);
        }

        if (! Auth::attempt(['mobile' => $this->mobile, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            $this->reset('password');

            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل یا رمز عبور اشتباه است.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $this->reset('password');

            throw ValidationException::withMessages([
                'mobile' => 'حساب کاربری شما غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.',
            ]);
        }

        RateLimiter::clear($key);
        Session::regenerate();

        $this->redirectIntended(default: $user->homeUrl());
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }

    protected function throttleKey(): string
    {
        return 'login|'.$this->mobile.'|'.request()->ip();
    }
}
