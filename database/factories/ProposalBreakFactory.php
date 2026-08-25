<?php

namespace Database\Factories;

use App\Models\ProposalBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalBreak>
 */
class ProposalBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'break_in' => '12:00',
            'break_out' => '13:00',
        ];
    }
}
