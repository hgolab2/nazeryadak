<?php

namespace App\Enums;

enum ProductCategory: int
{
    case ENGINE = 1;
    case CONSUMABLES = 2;
    case CHASSIS_BODY = 3;
    case GEARBOX = 4;
    case FUEL_SYSTEM = 5;
    case BRAKE_SUSPENSION = 6;
    case ELECTRICAL = 7;
    case COOLING = 8;
    case EXHAUST = 9;
    case INTERIOR = 10;
    case OTHER = 11;

    public function label(): string
    {
        return match ($this) {
            self::ENGINE => 'موتور و اجزای متعلقه',
            self::CONSUMABLES => 'قطعات مصرفی (روغن، فیلتر، تسمه، لنت)',
            self::CHASSIS_BODY => 'شاسی و بدنه',
            self::GEARBOX => 'گیربکس و دیفرانسیل',
            self::FUEL_SYSTEM => 'سوخت‌رسانی و جرقه',
            self::BRAKE_SUSPENSION => 'چرخ، ترمز و جلوبندی',
            self::ELECTRICAL => 'برق و سنسورهای خودرو',
            self::COOLING => 'سیستم خنک‌کننده',
            self::EXHAUST => 'اگزوز و سیستم تهویه',
            self::INTERIOR => 'تزئینات داخلی و خارجی',
            self::OTHER => 'سایر قطعات',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::ENGINE => 'موتور-و-اجزای-متعلقه',
            self::CONSUMABLES => 'قطعات-مصرفی',
            self::CHASSIS_BODY => 'شاسی-و-بدنه',
            self::GEARBOX => 'سیستم-گیربکس-و-دیفرانسیل',
            self::FUEL_SYSTEM => 'سیستم-سوخت‌رسانی',
            self::BRAKE_SUSPENSION => 'سیستم-چرخ-و-ترمز',
            self::ELECTRICAL => 'سیستم-برق',
            self::COOLING => 'سیستم-خنک-کننده',
            self::EXHAUST => 'اگزوز',
            self::INTERIOR => 'تزئینات',
            self::OTHER => 'سایر',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ENGINE => 'fa-cogs',
            self::CONSUMABLES => 'fa-oil-can',
            self::CHASSIS_BODY => 'fa-car',
            self::GEARBOX => 'fa-cog',
            self::FUEL_SYSTEM => 'fa-gas-pump',
            self::BRAKE_SUSPENSION => 'fa-compact-disc',
            self::ELECTRICAL => 'fa-bolt',
            self::COOLING => 'fa-fan',
            self::EXHAUST => 'fa-wind',
            self::INTERIOR => 'fa-chair',
            self::OTHER => 'fa-puzzle-piece',
        };
    }

    public static function list(): array
    {
        return array_map(fn ($case) => [
            'id'    => $case->value,
            'label' => $case->label(),
            'slug'  => $case->slug(),
        ], self::cases());
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }
        return null;
    }
}
