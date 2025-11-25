<?php

namespace NahidFerdous\Guardian\Database\Factories;

use NahidFerdous\Guardian\Models\Privilege;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Privilege>
 */
class PrivilegeFactory extends Factory
{
    protected $model = Privilege::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
        ];
    }
}
