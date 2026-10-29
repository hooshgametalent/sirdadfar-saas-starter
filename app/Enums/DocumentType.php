<?php

namespace App\Enums;

enum DocumentType: string
{
    case NationalCard = 'national_card';
    case DrivingLicense = 'driving_license';
    case VehicleCard = 'vehicle_card';
    case SmartCard = 'smart_card';
    case Insurance = 'insurance';

    public function label(): string
    {
        return match ($this) {
            self::NationalCard => 'کارت ملی',
            self::DrivingLicense => 'گواهینامه رانندگی',
            self::VehicleCard => 'کارت خودرو',
            self::SmartCard => 'کارت هوشمند راننده',
            self::Insurance => 'بیمه‌نامه',
        };
    }
}
