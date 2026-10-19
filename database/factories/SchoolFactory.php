<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' University';

        return [
            'name' => $name,
            'short_name' => Str::upper(Str::substr(Str::slug($name), 0, 3)),
            'location' => fake()->city(),
            'institution_type' => fake()->randomElement(['State University', 'Private University']),
            'website' => fake()->url(),
        ];
    }
}
