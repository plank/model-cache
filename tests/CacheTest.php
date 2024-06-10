<?php

use Plank\ModelCache\Tests\Models\Document;

it('remembers values on the model', function (string $driver) {
    config()->set('cache.default', $driver);

    $value = Document::remember('test', fn () => 'test');
    expect($value)->toBe('test');

    $value = Document::remember('test', fn () => 'changed');
    expect($value)->toBe('test');
})->with([
    'tagged cache' => 'redis',
    'taggless cache' => 'file',
]);
