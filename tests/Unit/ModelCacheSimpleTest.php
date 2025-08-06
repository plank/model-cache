<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Simple Model Cache Test', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('creates the same cache key for identical closures', function () {
        $closure = function () {
            return 'test_value';
        };
        
        $reflection = new \ReflectionClass(User::class);
        $method = $reflection->getMethod('cachableKey');
        $method->setAccessible(true);
        
        $key1 = $method->invokeArgs(null, [$closure]);
        $key2 = $method->invokeArgs(null, [$closure]);
        
        expect($key1)->toBe($key2);
    });

    it('tests remember with exact same closure variable', function () {
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'same_closure_value';
        };
        
        $result1 = User::remember($closure);
        $result2 = User::remember($closure);
        
        expect($result1)->toBe('same_closure_value');
        expect($result2)->toBe('same_closure_value');
        expect($callCount)->toBe(1);
    });

    it('tests whether cache tags are the problem', function () {
        
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'no_tags_value';
        };
        
        $result1 = User::remember($closure);
        $result2 = User::remember($closure);
        
        expect($result1)->toBe('no_tags_value');
        expect($result2)->toBe('no_tags_value');
        expect($callCount)->toBe(1);
    });

    it('manually inspects cache key generation for debugging', function () {
        $closure = function () {
            return 'debug_value';
        };
        
        // Access protected method
        $reflection = new \ReflectionClass(User::class);
        $cachableKeyMethod = $reflection->getMethod('cachableKey');
        $cachableKeyMethod->setAccessible(true);
        
        $key = $cachableKeyMethod->invokeArgs(null, [$closure]);
        
        // Add model cache prefix manually
        $prefixedKey = User::modelCachePrefix() ? User::modelCachePrefix() . ':' . $key : $key;
        
        // Verify the key format
        expect($key)->toContain('ModelCacheSimpleTest::{closure}');
        expect($prefixedKey)->toBe($key); // No prefix for User model
        expect(User::modelCacheTag())->toBe('plank_model_cache_tests_models_user');
        
        // Check if key exists in cache
        expect(Cache::has($prefixedKey))->toBeFalse();
        
        // Put something in cache manually
        Cache::put($prefixedKey, 'manual_value', 60);
        expect(Cache::get($prefixedKey))->toBe('manual_value');
    });
});