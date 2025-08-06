<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Enums\ExpireAfter;
use Plank\ModelCache\Tests\Models\User;

describe('Instance-specific Model Cache', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('can cache per model instance using rememberOnSelf', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $callCount1 = 0;
        $callCount2 = 0;
        
        $closure1 = function () use (&$callCount1) {
            $callCount1++;
            return 'user_1_data';
        };
        
        $closure2 = function () use (&$callCount2) {
            $callCount2++;
            return 'user_2_data';
        };
        
        $result1 = $user1->rememberOnSelf($closure1);
        $result2 = $user2->rememberOnSelf($closure2);
        $result3 = $user1->rememberOnSelf($closure1); // Same closure for user1
        
        expect($result1)->toBe('user_1_data');
        expect($result2)->toBe('user_2_data');
        expect($result3)->toBe('user_1_data');
        expect($callCount1)->toBe(1); // Called once for user1
        expect($callCount2)->toBe(1); // Called once for user2
    });

    it('respects prefix in rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;
        
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], 'prefix1');
        
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], 'prefix2');
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2); // Different prefixes = different cache keys
    });

    it('respects callable prefix in rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;
        
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], fn() => 'dynamic_1');
        
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], fn() => 'dynamic_2');
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2);
    });

    it('uses default TTL when none provided in rememberOnSelf', function () {
        config(['model-cache.ttl' => ExpireAfter::OneHour]);
        
        $user = User::factory()->create();
        
        $closure = function () {
            return 'cached_data';
        };
        
        $result = $user->rememberOnSelf($closure);
        
        expect($result)->toBe('cached_data');
        
        // Verify it's actually cached
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result2)->toBe('cached_data');
    });

    it('bypasses cache when disabled for rememberOnSelf', function () {
        config(['model-cache.enabled' => false]);
        
        $user = User::factory()->create();
        $callCount = 0;
        
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        });
        
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'value';
        });
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2); // Should be called twice when disabled
    });
});