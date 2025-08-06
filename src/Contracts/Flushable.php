<?php

namespace Plank\ModelCache\Contracts;

interface Flushable
{
    /**
     * Bust any Model Cache entries this model was tagged in
     */
    public function flushModelCache(): void;

    /**
     * Allow models to determine if they should skip flushing the Model Cache
     */
    public function shouldSkipFlushing(): bool;

    /**
     * Get the string that represents the tag for this model
     */
    public static function modelCacheTag(): string;

    /**
     * Get the string that represents the tag for this model instance
     */
    public function instanceCacheTag(): string;

    /**
     * Add a prefix to all tags and generated cache keys for this model
     */
    public static function modelCachePrefix(): string;
}
