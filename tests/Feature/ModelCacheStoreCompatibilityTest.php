<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Cache Store Compatibility', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('works with array cache store (non-taggable)', function () {
        // Array cache store doesn't support tags
        config(['cache.default' => 'array']);
        
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'array_cached_data';
        };
        
        $result1 = User::remember($closure);
        
        expect($result1)->toBe('array_cached_data');
        expect($callCount)->toBe(1);
        
        // Should be cached
        $result2 = User::remember($closure);
        
        expect($result2)->toBe('array_cached_data');
        expect($callCount)->toBe(1);
    });

    it('flushes entire cache when using non-taggable store', function () {
        // Array cache store doesn't support tags, so flushModelCache should flush all
        config(['cache.default' => 'array']);
        
        $callCount = 0;
        
        // Cache some data
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'before_flush';
        });
        
        expect($result1)->toBe('before_flush');
        expect($callCount)->toBe(1);
        
        // Create and save a user - this should flush entire cache
        $user = User::factory()->create();
        
        // Cache should be completely cleared
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'after_flush';
        });
        
        expect($result2)->toBe('after_flush');
        expect($callCount)->toBe(2);
    });

    it('works with instance caching on non-taggable store', function () {
        config(['cache.default' => 'array']);
        
        $user = User::factory()->create();
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'instance_array_data';
        };
        
        $result1 = $user->rememberOnSelf($closure);
        
        expect($result1)->toBe('instance_array_data');
        expect($callCount)->toBe(1);
        
        // Should be cached
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result2)->toBe('instance_array_data');
        expect($callCount)->toBe(1);
    });

    it('works correctly with array cache store', function () {
        // This tests that the package works with non-taggable stores
        config(['cache.default' => 'array']);
        
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'non_tagged_data';
        };
        
        $result = User::remember($closure);
        
        expect($result)->toBe('non_tagged_data');
        expect($callCount)->toBe(1);
        
        // Should still be cached
        $result2 = User::remember($closure);
        
        expect($result2)->toBe('non_tagged_data');
        expect($callCount)->toBe(1);
    });

    it('uses model cache prefix with non-taggable stores', function () {        
        config(['cache.default' => 'array']);
        
        $user = User::factory()->create();
        $callCount = 0;
        
        // The prefix should be included in the cache key even with non-taggable stores
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'prefixed_data';
        };
        
        $result = $user->rememberOnSelf($closure);
        
        expect($result)->toBe('prefixed_data');
        expect($callCount)->toBe(1);
        
        // Should be cached with the prefix
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result2)->toBe('prefixed_data');
        expect($callCount)->toBe(1);
    });
});