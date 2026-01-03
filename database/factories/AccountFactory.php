<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountTypes = ['checking', 'savings', 'investment', 'credit'];
        $institutions = [
            'Banco do Brasil',
            'Caixa Econômica',
            'Itaú',
            'Bradesco',
            'Santander',
            'Nubank',
            'Inter',
            'XP Investimentos',
            'BTG Pactual',
        ];

        $initialBalance = $this->faker->numberBetween(0, 1000000);

        return [
            'user_id' => User::factory(),
            'account_name' => $this->faker->randomElement([
                'Conta Corrente',
                'Poupança',
                'Investimentos',
                'Conta Salário',
                'Conta Digital',
                'Cartão de Crédito',
            ]),
            'account_type' => $this->faker->randomElement($accountTypes),
            'initial_balance_cents' => $initialBalance,
            'current_balance_cents' => $initialBalance + $this->faker->numberBetween(-50000, 100000),
            'currency' => 'BRL',
            'institution_name' => $this->faker->randomElement($institutions),
            'is_active' => $this->faker->boolean(90),
            'display_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function checking(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_name' => 'Conta Corrente',
            'account_type' => 'checking',
        ]);
    }

    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_name' => 'Poupança',
            'account_type' => 'savings',
        ]);
    }

    public function investment(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_name' => 'Investimentos',
            'account_type' => 'investment',
        ]);
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_name' => 'Cartão de Crédito',
            'account_type' => 'credit',
            'initial_balance_cents' => 0,
            'current_balance_cents' => $this->faker->numberBetween(-50000, 0),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
