<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\Post;
use Plank\ModelCache\Tests\Models\User;

describe('Cache Invalidation', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('flushes model cache when model is saved', function () {
        $user = User::factory()->create();
        $callCount = 0;

        // Cache some data
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;

            return 'original_data';
        });

        expect($result1)->toBe('original_data');
        expect($callCount)->toBe(1);

        // Update the model - this should flush the cache
        $user->update(['name' => 'Updated Name']);

        // Cache should be cleared, so closure should be called again
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;

            return 'new_data';
        });

        expect($result2)->toBe('new_data');
        expect($callCount)->toBe(2);
    });

    it('flushes model cache when model is deleted', function () {
        $user = User::factory()->create();
        $callCount = 0;

        // Cache some data
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;

            return 'cached_data';
        });

        expect($result1)->toBe('cached_data');
        expect($callCount)->toBe(1);

        // Delete the model - this should flush the cache
        $user->delete();

        // Create a new user with same ID to test cache is cleared
        $newUser = User::factory()->create(['id' => $user->id]);

        $result2 = $newUser->rememberOnSelf(function () use (&$callCount) {
            $callCount++;

            return 'new_cached_data';
        });

        expect($result2)->toBe('new_cached_data');
        expect($callCount)->toBe(2);
    });

    it('flushes class-level cache when any model instance changes', function () {
        $callCount = 0;

        // Cache some data at class level
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'class_cached_data';
        });

        expect($result1)->toBe('class_cached_data');
        expect($callCount)->toBe(1);

        // Create a new user - this should flush class-level cache
        User::factory()->create();

        // Class cache should be cleared, so closure should be called again
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'new_class_data';
        });

        expect($result2)->toBe('new_class_data');
        expect($callCount)->toBe(2);
    });

    it('only flushes cache for the specific model class', function () {
        $userCallCount = 0;
        $postCallCount = 0;

        // Cache data for both User and Post
        $userClosure = function () use (&$userCallCount) {
            $userCallCount++;

            return 'user_data';
        };

        $postClosure = function () use (&$postCallCount) {
            $postCallCount++;

            return 'post_data';
        };

        $userResult1 = User::remember($userClosure);
        $postResult1 = Post::remember($postClosure);

        expect($userResult1)->toBe('user_data');
        expect($postResult1)->toBe('post_data');
        expect($userCallCount)->toBe(1);
        expect($postCallCount)->toBe(1);

        // Create a new user - should only flush User cache
        User::factory()->create();

        // User cache should be cleared, so we need a new closure that returns different data
        $newUserClosure = function () use (&$userCallCount) {
            $userCallCount++;

            return 'new_user_data';
        };

        $userResult2 = User::remember($newUserClosure);

        // Post cache should still be intact
        $postResult2 = Post::remember($postClosure);

        expect($userResult2)->toBe('new_user_data');
        expect($postResult2)->toBe('post_data');
        expect($userCallCount)->toBe(2);
        expect($postCallCount)->toBe(1); // Should not have increased
    });

    it('can skip flushing when shouldSkipFlushing returns true', function () {
        $user = User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Mock shouldSkipFlushing to return true
        $user = new class extends User
        {
            protected $table = 'users';

            public function shouldSkipFlushing(): bool
            {
                return true;
            }
        };
        $user->fill(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        $callCount = 0;

        // Use same closure to ensure same cache key
        $closure = function () use (&$callCount) {
            $callCount++;

            return 'cached_data';
        };

        // Cache some data
        $result1 = $user->rememberOnSelf($closure);

        expect($result1)->toBe('cached_data');
        expect($callCount)->toBe(1);

        // Update the model - this should NOT flush the cache due to shouldSkipFlushing
        $user->update(['name' => 'Updated Name']);

        // Cache should still be intact - same closure should return cached data
        $result2 = $user->rememberOnSelf($closure);

        expect($result2)->toBe('cached_data'); // Should return original cached data
        expect($callCount)->toBe(1); // Should not have been called again
    });
});
