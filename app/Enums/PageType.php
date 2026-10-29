<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageType: string implements HasLabel
{
    case Route = 'route';
    case Service = 'service';
    case StaticPage = 'static';

    public function label(): string
    {
        return match ($this) {
            self::Route => 'صفحه مسیر',
            self::Service => 'صفحه خدمت',
            self::StaticPage => 'صفحه ثابت',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
