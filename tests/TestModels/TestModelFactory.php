<?php

namespace christopheraseidl\ModelFiler\Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition()
    {
        return [
            'string' => $this->faker->imageUrl(),
            'array' => [$this->faker->word.'pdf', $this->faker->word.'md'],
        ];
    }

    public function withStringFillable($path)
    {
        return $this->state(function (array $attributes) use ($path) {
            return [
                'string' => $path,
            ];
        });
    }

    public function withArrayFillable(array $paths)
    {
        return $this->state(function (array $attributes) use ($paths) {
            return [
                'array' => $paths,
            ];
        });
    }
}
