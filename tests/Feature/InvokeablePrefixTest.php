<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

class TestPrefixInvokeable
{
    public function __invoke(): string
    {
        return 'invoked-prefix';
    }
}

class DynamicPrefixInvokeable
{
    public function __invoke(): string
    {
        return 'dynamic-'.time();
    }
}

describe('Invokeable Class Prefix Support', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('can use invokeable class string as prefix in remember method', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result1 = User::remember($closure, [], TestPrefixInvokeable::class);
        $result2 = User::remember($closure, [], TestPrefixInvokeable::class);

        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('can use invokeable class string as prefix in rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'instance_data';
        };

        $result1 = $user->rememberOnSelf($closure, [], TestPrefixInvokeable::class);
        $result2 = $user->rememberOnSelf($closure, [], TestPrefixInvokeable::class);

        expect($result1)->toBe('instance_data');
        expect($result2)->toBe('instance_data');
        expect($callCount)->toBe(1);
    });

    it('invokes the class each time to get prefix value', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value_'.$callCount;
        };

        // First call - should cache with dynamic prefix
        $result1 = User::remember($closure, [], DynamicPrefixInvokeable::class);

        // Sleep to ensure time() returns different value
        sleep(1);

        // Second call with same invokeable class but different time
        // Should be a cache miss because prefix is different
        $result2 = User::remember($closure, [], DynamicPrefixInvokeable::class);

        expect($result1)->toBe('cached_value_1');
        expect($result2)->toBe('cached_value_2');
        expect($callCount)->toBe(2);
    });

    it('creates separate cache entries for different invokeable prefixes', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'value_'.$callCount;
        };

        $result1 = User::remember($closure, [], TestPrefixInvokeable::class);
        $result2 = User::remember($closure, [], DynamicPrefixInvokeable::class);

        expect($result1)->toBe('value_1');
        expect($result2)->toBe('value_2');
        expect($callCount)->toBe(2);
    });

    it('handles non-invokeable class strings as regular strings', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        // Use a class that exists but is not invokeable
        $result1 = User::remember($closure, [], User::class);
        $result2 = User::remember($closure, [], User::class);

        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('handles non-existent class strings as regular strings', function () {
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_value';
        };

        $result1 = User::remember($closure, [], 'NonExistentClass');
        $result2 = User::remember($closure, [], 'NonExistentClass');

        expect($result1)->toBe('cached_value');
        expect($result2)->toBe('cached_value');
        expect($callCount)->toBe(1);
    });

    it('works with rememberOnSelf and different user instances', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'user_data_'.$callCount;
        };

        $result1 = $user1->rememberOnSelf($closure, [], TestPrefixInvokeable::class);
        $result2 = $user2->rememberOnSelf($closure, [], TestPrefixInvokeable::class);

        // Each user instance should have its own cache entry even with same prefix
        expect($result1)->toBe('user_data_1');
        expect($result2)->toBe('user_data_2');
        expect($callCount)->toBe(2);
    });
});
