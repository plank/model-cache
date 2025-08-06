<?php

use Illuminate\Support\Facades\Cache;
use Plank\ModelCache\Tests\Models\Post;
use Plank\ModelCache\Tests\Models\Tag;
use Plank\ModelCache\Tests\Models\User;

describe('Cache Tags Functionality', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('generates correct model cache tag', function () {
        expect(User::modelCacheTag())->toBe('plank_model_cache_tests_models_user');
        expect(Post::modelCacheTag())->toBe('plank_model_cache_tests_models_post');
    });

    it('generates correct instance cache tag', function () {
        $user = User::factory()->create();

        expect($user->instanceCacheTag())->toBe('plank_model_cache_tests_models_user:'.$user->id);
    });

    it('respects model cache prefix in tags', function () {
        expect(Tag::modelCacheTag())->toBe('test_prefix:plank_model_cache_tests_models_tag');

        $tag = Tag::factory()->create();
        expect($tag->instanceCacheTag())->toBe('test_prefix:plank_model_cache_tests_models_tag:'.$tag->id);
    });

    it('can use additional tags with remember method', function () {
        $callCount = 0;

        // Use same closure variable to ensure same cache key
        $closure = function () use (&$callCount) {
            $callCount++;

            return 'tagged_data';
        };

        // Use class names as tags since they work (handleTag converts them properly)
        $result = User::remember($closure, [Post::class]);

        expect($result)->toBe('tagged_data');
        expect($callCount)->toBe(1);

        // Verify it's cached - same closure, same tags should use cache
        $result2 = User::remember($closure, [Post::class]);

        expect($result2)->toBe('tagged_data');
        expect($callCount)->toBe(1); // Should not increment
    });

    it('can use additional tags with rememberOnSelf method', function () {
        $user = User::factory()->create();
        $callCount = 0;

        // Use same closure variable to ensure same cache key
        $closure = function () use (&$callCount) {
            $callCount++;

            return 'tagged_instance_data';
        };

        // Use class names as tags since they work (handleTag converts them properly)
        $result = $user->rememberOnSelf($closure, [Post::class]);

        expect($result)->toBe('tagged_instance_data');
        expect($callCount)->toBe(1);

        // Verify it's cached - same closure, same tags should use cache
        $result2 = $user->rememberOnSelf($closure, [Post::class]);

        expect($result2)->toBe('tagged_instance_data');
        expect($callCount)->toBe(1); // Should not increment
    });

    it('handles cachable class names as tags', function () {
        $callCount = 0;

        // Use Post class as a tag - should be converted to model cache tag
        $result = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'cross_model_data';
        }, [Post::class]);

        expect($result)->toBe('cross_model_data');
        expect($callCount)->toBe(1);

        // Create a post - this should invalidate the User cache because Post class was used as tag
        Post::factory()->create();

        // Cache should be cleared due to Post model change
        $result2 = User::remember(function () use (&$callCount) {
            $callCount++;

            return 'new_cross_model_data';
        }, [Post::class]);

        expect($result2)->toBe('new_cross_model_data');
        expect($callCount)->toBe(2);
    });

    it('can use custom string tags successfully', function () {
        // This test verifies the withCacheTagPrefix bug has been fixed
        $callCount = 0;

        $closure = function () use (&$callCount) {
            $callCount++;

            return 'custom_tagged_data';
        };

        // This should now work without throwing an exception
        $result1 = User::remember($closure, ['custom_tag', 'another_tag']);
        $result2 = User::remember($closure, ['custom_tag', 'another_tag']);

        expect($result1)->toBe('custom_tagged_data');
        expect($result2)->toBe('custom_tagged_data');
        expect($callCount)->toBe(1); // Should be cached
    });
});
