<?php

namespace App\Filament\Pages\Auth;

use App\Support\Mobile;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Admin panel sign in with mobile number and password instead of email.
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getMobileFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
            ->label('شماره موبایل')
            ->placeholder('09xxxxxxxxx')
            ->tel()
            ->required()
            ->maxLength(20)
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'mobile' => Mobile::normalize($data['mobile'] ?? ''),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.mobile' => 'شماره موبایل یا رمز عبور نادرست است یا به پنل مدیریت دسترسی ندارید.',
        ]);
    }
}
