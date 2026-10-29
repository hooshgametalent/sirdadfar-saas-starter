<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Mobile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
}
