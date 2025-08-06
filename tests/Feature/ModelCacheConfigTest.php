<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Enums\ExpireAfter;
use Plank\ModelCache\Tests\Models\User;

describe('Model Cache Configuration', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('respects enabled configuration', function () {
        config(['model-cache.enabled' => true]);
        
        expect(User::modelCacheDisabled())->toBeFalse();
        
        config(['model-cache.enabled' => false]);
        
        expect(User::modelCacheDisabled())->toBeTrue();
    });

    it('defaults to disabled when config is not set', function () {
        config(['model-cache.enabled' => null]);
        
        expect(User::modelCacheDisabled())->toBeTrue();
    });

    it('uses configured TTL as default', function () {
        config(['model-cache.ttl' => ExpireAfter::OneHour]);
        
        expect(ExpireAfter::default())->toBe(ExpireAfter::OneHour);
        
        config(['model-cache.ttl' => ExpireAfter::Forever]);
        
        expect(ExpireAfter::default())->toBe(ExpireAfter::Forever);
    });

    it('bypasses caching entirely when disabled', function () {
        config(['model-cache.enabled' => false]);
        
        $callCount = 0;
        
        // Multiple calls should always execute the closure
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'call_' . $callCount;
        });
        
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'call_' . $callCount;
        });
        
        $result3 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'call_' . $callCount;
        });
        
        expect($result1)->toBe('call_1');
        expect($result2)->toBe('call_2');
        expect($result3)->toBe('call_3');
        expect($callCount)->toBe(3);
    });

    it('bypasses instance caching when disabled', function () {
        config(['model-cache.enabled' => false]);
        
        $user = User::factory()->create();
        $callCount = 0;
        
        // Multiple calls should always execute the closure
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'instance_call_' . $callCount;
        });
        
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'instance_call_' . $callCount;
        });
        
        expect($result1)->toBe('instance_call_1');
        expect($result2)->toBe('instance_call_2');
        expect($callCount)->toBe(2);
    });

    it('works normally when enabled', function () {
        config(['model-cache.enabled' => true]);
        
        $callCount = 0;
        
        // Multiple calls should use cache
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'cached_call';
        };
        
        $result1 = User::remember($closure);
        $result2 = User::remember($closure);
        
        expect($result1)->toBe('cached_call');
        expect($result2)->toBe('cached_call');
        expect($callCount)->toBe(1); // Should only be called once due to caching
    });
});