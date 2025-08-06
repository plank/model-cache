<?php

namespace Plank\ModelCache\Tests\Helper\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\ModelCache\Tests\Models\Post;
use Plank\ModelCache\Tests\Models\User;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'user_id' => User::factory(),
        ];
    }
}
