<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Basic Functionality Without Tags', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('can cache a simple closure', function () {
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'cached_value';
        };
        
        $result1 = User::remember($closure);
        $result2 = User::remember($closure);
        
        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('works with rememberOnSelf for instances', function () {
        $user = User::factory()->create();
        $callCount = 0;
        
        $closure = function () use (&$callCount) {
            $callCount++;
            return 'instance_data';
        };
        
        $result1 = $user->rememberOnSelf($closure);
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result1)->toBe('instance_data');
        expect($result2)->toBe('instance_data');
        expect($callCount)->toBe(1);
    });

    it('can handle different instances separately', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $callCount = 0;
        
        $result1 = $user1->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'user1_data';
        });
        
        $result2 = $user2->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'user2_data';
        });
        
        expect($result1)->toBe('user1_data');
        expect($result2)->toBe('user2_data');
        expect($callCount)->toBe(2);
    });

    it('invalidates cache when model is updated', function () {
        $user = User::factory()->create();
        $callCount = 0;
        
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'before_update';
        });
        
        expect($result1)->toBe('before_update');
        expect($callCount)->toBe(1);
        
        $user->update(['name' => 'Updated']);
        
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'after_update';
        });
        
        expect($result2)->toBe('after_update');
        expect($callCount)->toBe(2);
    });
});