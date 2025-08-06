<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Cache Debugging', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('checks if cache is working at all', function () {
        // Direct cache test
        Cache::put('test_key', 'test_value', 60);
        expect(Cache::get('test_key'))->toBe('test_value');
    });

    it('checks model cache configuration', function () {
        expect(User::modelCacheDisabled())->toBeFalse();
        expect(config('model-cache.enabled'))->toBeTrue();
    });

    it('debugs closure key generation', function () {
        $closure = function () {
            return 'test';
        };
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass(User::class);
        $method = $reflection->getMethod('cachableKey');
        $method->setAccessible(true);
        
        $key = $method->invokeArgs(null, [$closure]);
        
        expect($key)->toBeString();
        expect(strlen($key))->toBeGreaterThan(0);
        expect($key)->toContain('CacheDebuggingTest::{closure}');
    });

    it('checks cache store type', function () {
        $store = Cache::getStore();
        
        expect($store)->toBeInstanceOf(\Illuminate\Cache\ArrayStore::class);
        expect(method_exists($store, 'tags'))->toBeTrue();
    });

    it('manually tests cache remember', function () {
        $callCount = 0;
        
        $result1 = Cache::remember('manual_test', 60, function () use (&$callCount) {
            $callCount++;
            return 'manual_cached';
        });
        
        $result2 = Cache::remember('manual_test', 60, function () use (&$callCount) {
            $callCount++;
            return 'manual_cached';
        });
        
        expect($result1)->toBe('manual_cached');
        expect($result2)->toBe('manual_cached');
        expect($callCount)->toBe(1);
    });
});