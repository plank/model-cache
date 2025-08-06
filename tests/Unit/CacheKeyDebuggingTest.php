<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Cache Key Debugging', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('demonstrates different closures create different cache keys', function () {
        // Use reflection to access the protected cachableKey method
        $reflection = new \ReflectionClass(User::class);
        $method = $reflection->getMethod('cachableKey');
        $method->setAccessible(true);

        // Two identical closures defined on different lines
        $closure1 = function () {
            return 'value';
        };

        $closure2 = function () {
            return 'value';
        };

        $key1 = $method->invokeArgs(null, [$closure1]);
        $key2 = $method->invokeArgs(null, [$closure2]);

        expect($key1)->not->toBe($key2); // Different line numbers = different keys
        expect($key1)->toContain('CacheKeyDebuggingTest::{closure}');
        expect($key2)->toContain('CacheKeyDebuggingTest::{closure}');
    });

    it('demonstrates same closure variable creates same cache key', function () {
        $reflection = new \ReflectionClass(User::class);
        $method = $reflection->getMethod('cachableKey');
        $method->setAccessible(true);

        // Same closure variable used twice
        $closure = function () {
            return 'value';
        };

        $key1 = $method->invokeArgs(null, [$closure]);
        $key2 = $method->invokeArgs(null, [$closure]);

        expect($key1)->toBe($key2); // Same closure = same key
        expect($key1)->toContain('CacheKeyDebuggingTest::{closure}');
    });

    it('proves the failing test issue - different inline closures', function () {
        $callCount = 0;

        // This mirrors the failing test - two separate closures
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        });

        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        });

        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(2); // Called twice because different cache keys!
    });

    it('proves the correct approach - same closure variable', function () {
        $callCount = 0;

        // This is the correct approach - same closure variable
        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result1 = User::remember($closure);
        $result2 = User::remember($closure);

        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1); // Called once because same cache key!
    });
});
