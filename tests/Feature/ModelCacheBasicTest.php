<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Enums\ExpireAfter;
use Plank\ModelCache\Tests\Models\User;

describe('Basic Model Cache Functionality', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('can cache a closure result using remember method', function () {
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'cached_value';
        };
        
        $result1 = User::remember($closure);
        $result2 = User::remember($closure);
        
        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1); // Should only be called once
    });

    it('can cache different closures separately', function () {
        $callCount1 = 0;
        $callCount2 = 0;
        
        $result1 = User::remember(function () use (&$callCount1) {
            $callCount1++;
            return 'value_1';
        });
        
        $result2 = User::remember(function () use (&$callCount2) {
            $callCount2++;
            return 'value_2';
        });
        
        expect($result1)->toBe('value_1');
        expect($result2)->toBe('value_2');
        expect($callCount1)->toBe(1);
        expect($callCount2)->toBe(1);
    });

    it('respects cache prefix in remember method', function () {
        $callCount = 0;
        
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], 'prefix1');
        
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], 'prefix2');
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2); // Different prefixes = different cache keys
    });

    it('respects callable prefix in remember method', function () {
        $callCount = 0;
        
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], fn() => 'dynamic_prefix_1');
        
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        }, [], fn() => 'dynamic_prefix_2');
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2);
    });

    it('bypasses cache when disabled in config', function () {
        config(['model-cache.enabled' => false]);
        
        $callCount = 0;
        
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        });
        
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'value';
        });
        
        expect($result1)->toBe('value');
        expect($result2)->toBe('value');
        expect($callCount)->toBe(2); // Should be called twice when disabled
    });
});