<?php

use Plank\ModelCache\Enums\ExpireAfter;

describe('ExpireAfter Enum', function () {
    it('returns correct seconds for each enum case', function () {
        expect(ExpireAfter::Forever->inSeconds())->toBeNull();
        expect(ExpireAfter::OneMinute->inSeconds())->toBe(60);
        expect(ExpireAfter::FiveMinutes->inSeconds())->toBe(300);
        expect(ExpireAfter::TenMinutes->inSeconds())->toBe(600);
        expect(ExpireAfter::FifteenMinutes->inSeconds())->toBe(900);
        expect(ExpireAfter::ThirtyMinutes->inSeconds())->toBe(1800);
        expect(ExpireAfter::FortyFiveMinutes->inSeconds())->toBe(2700);
        expect(ExpireAfter::OneHour->inSeconds())->toBe(3600);
        expect(ExpireAfter::OneDay->inSeconds())->toBe(86400);
        expect(ExpireAfter::OneWeek->inSeconds())->toBe(604800);
        expect(ExpireAfter::OneMonth->inSeconds())->toBe(2592000);
        expect(ExpireAfter::OneYear->inSeconds())->toBe(31536000);
    });

    it('returns default TTL from config', function () {
        config(['model-cache.ttl' => ExpireAfter::OneHour]);
        
        expect(ExpireAfter::default())->toBe(ExpireAfter::OneHour);
    });

    it('handles Forever as default TTL', function () {
        config(['model-cache.ttl' => ExpireAfter::Forever]);
        
        expect(ExpireAfter::default())->toBe(ExpireAfter::Forever);
        expect(ExpireAfter::default()->inSeconds())->toBeNull();
    });
});