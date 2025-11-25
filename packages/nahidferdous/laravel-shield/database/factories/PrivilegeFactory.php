<?php

namespace NahidFerdous\Shield\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NahidFerdous\Shield\Models\Privilege;

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
