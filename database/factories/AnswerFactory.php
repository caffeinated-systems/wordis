<?php

namespace Database\Factories;

use App\Models\Answer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('-10 years', '-1 day')->format('Y-m-d'),
            'word' => fake()->unique()->randomElement(Answer::pool()),
        ];
    }

    /**
     * Make this today's answer.
     */
    public function today(): static
    {
        return $this->state(fn (): array => ['date' => today()->toDateString()]);
    }
}
