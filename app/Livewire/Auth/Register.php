<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Jalali;
use App\Support\Mobile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Self registration for shippers and drivers only. Staff roles (admin,
 * operator, content editor) are assigned by an admin in the panel.
 * Mobile ownership is not verified by SMS yet; App\Contracts\SmsSender can
 * be used to add an OTP step later.
 */
#[Layout('layouts.app')]
#[Title('ثبت‌نام')]
class Register extends Component
{
    /**
     * Registration attempts allowed per IP address within DECAY_SECONDS.
     * Every submit counts, successful or not, to slow scripted account
     * creation and squatting on mobile numbers.
     */
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 600;

    public string $name = '';

    public string $mobile = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'shipper';

    /**
     * @return list<string>
     */
    public static function selfServiceRoles(): array
    {
        return [UserRole::Shipper->value, UserRole::Driver->value];
    }

    public function register(): void
    {
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'mobile' => 'تعداد درخواست‌های ثبت‌نام از این دستگاه زیاد است. لطفاً '.Jalali::toPersianDigits($seconds).' ثانیه دیگر دوباره تلاش کنید.',
            ]);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $this->mobile = Mobile::normalize($this->mobile);

        $data = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'mobile' => ['required', 'regex:'.Mobile::PATTERN, Rule::unique('users', 'mobile')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(self::selfServiceRoles())],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'mobile' => $data['mobile'],
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
            'is_active' => true,
        ]);

        Auth::login($user);
        Session::regenerate();

        $this->redirect($user->homeUrl());
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }

    protected function throttleKey(): string
    {
        return 'register|'.request()->ip();
    }
}
