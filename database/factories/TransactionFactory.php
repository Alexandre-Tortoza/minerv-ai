<?php

namespace Database\Factories;

use App\Models\{Account, Category, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense']);
        
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory()->state(['category_type' => $type]),
            'transaction_type' => $type,
            'amount_cents' => $this->faker->numberBetween(1000, 500000),
            'description' => $this->faker->sentence(3),
            'notes' => $this->faker->optional()->paragraph(),
            'transaction_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'is_paid' => $this->faker->boolean(70),
            'payment_method' => $this->faker->randomElement(['cash', 'debit_card', 'credit_card', 'bank_transfer', 'pix', 'other']),
            'reference_number' => $this->faker->optional()->numerify('####-####-####'),
            'tags' => $this->faker->optional()->randomElements(['urgente', 'importante'], 1),
        ];
    }

    public function income(): static { return $this->state(['transaction_type' => 'income']); }
    public function expense(): static { return $this->state(['transaction_type' => 'expense']); }
    public function paid(): static { return $this->state(['is_paid' => true]); }
}