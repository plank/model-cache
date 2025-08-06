<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Enums\ExpireAfter;
use Plank\ModelCache\Tests\Models\User;

describe('Cache TTL Handling', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('respects ExpireAfter enum TTL in remember method', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = User::remember($closure, [], '', ExpireAfter::OneMinute);

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached
        $result2 = User::remember($closure, [], '', ExpireAfter::OneMinute);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('respects integer TTL in remember method', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = User::remember($closure, [], '', 3600); // 1 hour in seconds

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached
        $result2 = User::remember($closure, [], '', 3600);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('handles null TTL (cache forever) in remember method', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = User::remember($closure, [], '', null);

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached
        $result2 = User::remember($closure, [], '', null);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('respects ExpireAfter enum TTL in rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = $user->rememberOnSelf($closure, [], '', ExpireAfter::FiveMinutes);

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached
        $result2 = $user->rememberOnSelf($closure, [], '', ExpireAfter::FiveMinutes);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('respects integer TTL in rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = $user->rememberOnSelf($closure, [], '', 1800); // 30 minutes in seconds

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached
        $result2 = $user->rememberOnSelf($closure, [], '', 1800);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('uses default TTL from config when none provided for rememberOnSelf', function () {
        config(['model-cache.ttl' => ExpireAfter::OneHour]);

        $user = User::factory()->create();
        $callCount = 0;

        // No TTL provided, should use default
        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result = $user->rememberOnSelf($closure);

        expect($result)->toBe('cached_value');
        expect($callCount)->toBe(1);

        // Verify it's cached with default TTL
        $result2 = $user->rememberOnSelf($closure);

        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('handles Forever TTL correctly', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'forever_cached';
        };

        $result = User::remember($closure, [], '', ExpireAfter::Forever);

        expect($result)->toBe('forever_cached');
        expect($callCount)->toBe(1);

        // Verify it's cached with null TTL (forever)
        $result2 = User::remember($closure, [], '', ExpireAfter::Forever);

        expect($result2)->toBe('forever_cached');
        expect($callCount)->toBe(1);
    });
});
