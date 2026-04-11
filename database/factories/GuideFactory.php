<?php

namespace Database\Factories;

use App\Enums\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => substr(fake()->sentence(), 0, 100),
            'channel_nr' => fake()->randomElement(Channel::cases()),
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ];
    }
}
