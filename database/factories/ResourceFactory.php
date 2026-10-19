<?php

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'type' => Resource::TYPE_CET,
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'link' => fake()->url(),
            'is_published' => true,
            'sort_order' => 0,
            'period' => fake()->monthName(),
            'coverage' => fake()->sentence(),
            'tips' => fake()->paragraph(),
        ];
    }

    public function scholarship(): static
    {
        return $this->state(fn () => [
            'type' => Resource::TYPE_SCHOLARSHIP,
            'grantor' => fake()->company(),
            'coverage' => fake()->sentence(),
            'criteria' => fake()->sentence(),
            'opens_at' => now()->subMonth(),
            'closes_at' => now()->addMonth(),
        ]);
    }

    public function university(): static
    {
        return $this->state(fn () => [
            'type' => Resource::TYPE_UNIVERSITY,
            'short_name' => Str::upper(fake()->lexify('??')),
            'location' => fake()->city(),
            'institution_type' => 'Private University',
            'top_programs' => [fake()->word(), fake()->word()],
            'entrance_exam' => Str::upper(fake()->lexify('????')),
            'tuition_range' => '₱50,000 - ₱90,000 per sem',
            'website' => fake()->url(),
            'logo_bg' => '#1e3a8a',
        ]);
    }

    public function strand(): static
    {
        return $this->state(fn () => [
            'type' => Resource::TYPE_STRAND,
            'emoji' => '🔬',
            'subtitle' => fake()->sentence(3),
            'alignments' => fake()->sentence(),
        ]);
    }
}
