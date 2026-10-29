<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Shipper = 'shipper';
    case Driver = 'driver';
    case Operator = 'operator';
    case Admin = 'admin';
    case ContentEditor = 'content_editor';

    /**
     * Roles that are allowed to sign in to the Filament admin panel.
     *
     * @return array<int, self>
     */
    public static function panelRoles(): array
    {
        return [self::Admin, self::Operator, self::ContentEditor];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::Shipper => 'صاحب بار',
            self::Driver => 'راننده',
            self::Operator => 'اپراتور',
            self::Admin => 'مدیر کل',
            self::ContentEditor => 'ویراستار محتوا',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
