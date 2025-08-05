<?php

namespace Plank\ModelCache\Enums;

enum ExpireAfter
{
    case Forever;
    case OneMinute;
    case FiveMinutes;
    case TenMinutes;
    case FifteenMinutes;
    case ThirtyMinutes;
    case FourtyFiveMinutes;
    case OneHour;
    case OneDay;
    case OneWeek;
    case OneMonth;
    case OneYear;

    public function inSeconds(): ?int
    {
        return match ($this) {
            self::Forever => null,
            self::OneMinute => 60,
            self::FiveMinutes => 300,
            self::TenMinutes => 600,
            self::FifteenMinutes => 900,
            self::ThirtyMinutes => 1800,
            self::FourtyFiveMinutes => 2700,
            self::OneHour => 3600,
            self::OneDay => 86400,
            self::OneWeek => 604800,
            self::OneMonth => 2592000,
            self::OneYear => 31536000,
        };
    }

    public static function default(): self
    {
        return config()->get('model-cache.ttl');
    }
}
