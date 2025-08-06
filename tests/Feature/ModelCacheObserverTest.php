<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\User;

describe('Model Cache Observer', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('observes model saved event and flushes cache', function () {
        $user = User::factory()->make();
        $callCount = 0;
        
        // Cache some data before creating the user
        $result1 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'initial_data';
        });
        
        expect($result1)->toBe('initial_data');
        expect($callCount)->toBe(1);
        
        // Save the user - this should trigger the observer and flush cache
        $user->save();
        
        // Cache should be cleared
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;
            return 'post_save_data';
        });
        
        expect($result2)->toBe('post_save_data');
        expect($callCount)->toBe(2);
    });

    it('observes model updated event and flushes cache', function () {
        $user = User::factory()->create();
        $callCount = 0;
        
        // Cache some data
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'before_update';
        });
        
        expect($result1)->toBe('before_update');
        expect($callCount)->toBe(1);
        
        // Update the user - this should trigger the observer
        $user->update(['name' => 'Updated Name']);
        
        // Cache should be cleared
        $result2 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'after_update';
        });
        
        expect($result2)->toBe('after_update');
        expect($callCount)->toBe(2);
    });

    it('observes model deleted event and flushes cache', function () {
        $user = User::factory()->create();
        $userId = $user->id;
        $callCount = 0;
        
        // Cache some data
        $result1 = $user->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'before_delete';
        });
        
        expect($result1)->toBe('before_delete');
        expect($callCount)->toBe(1);
        
        // Delete the user - this should trigger the observer
        $user->delete();
        
        // Create a new user with the same ID to test cache was cleared
        $newUser = new User();
        $newUser->id = $userId;
        $newUser->name = 'Test User';
        $newUser->email = 'test@example.com';
        $newUser->exists = true; // Simulate it exists in DB
        
        $result2 = $newUser->rememberOnSelf(function () use (&$callCount) {
            $callCount++;
            return 'after_delete';
        });
        
        expect($result2)->toBe('after_delete');
        expect($callCount)->toBe(2);
    });

    it('observes model restored event and flushes cache', function () {
        // Create a user that uses soft deletes for this test
        $user = new class extends User {
            use \Illuminate\Database\Eloquent\SoftDeletes;
            protected $table = 'users';
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
        
        // Soft delete the user
        $user->delete();
        
        // Restore the user - this should trigger the observer and flush cache
        $user->restore();
        
        // Cache should be cleared after restore, so closure should be called again
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result2)->toBe('cached_data');
        expect($callCount)->toBe(2); // Should increment due to cache flush
    });

    it('does not flush cache when shouldSkipFlushing returns true', function () {
        $user = new class extends User {
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
            return 'original_data';
        };
        
        // Cache some data
        $result1 = $user->rememberOnSelf($closure);
        
        expect($result1)->toBe('original_data');
        expect($callCount)->toBe(1);
        
        // Update the user - this should NOT flush cache due to shouldSkipFlushing
        $user->update(['name' => 'Updated Name']);
        
        // Cache should still be intact - same closure should return cached data
        $result2 = $user->rememberOnSelf($closure);
        
        expect($result2)->toBe('original_data'); // Should return cached data
        expect($callCount)->toBe(1); // Should not be called again
    });
});