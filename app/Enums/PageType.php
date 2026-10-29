<?php

namespace App\Enums;

enum PageType: string
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
}
