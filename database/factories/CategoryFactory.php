<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense']);

        $incomeCategories = [
            ['name' => 'Salário', 'icon' => 'briefcase', 'color' => '#10B981'],
            ['name' => 'Freelance', 'icon' => 'laptop', 'color' => '#3B82F6'],
            ['name' => 'Investimentos', 'icon' => 'trending-up', 'color' => '#8B5CF6'],
            ['name' => 'Outros', 'icon' => 'plus-circle', 'color' => '#6B7280'],
        ];

        $expenseCategories = [
            ['name' => 'Alimentação', 'icon' => 'shopping-cart', 'color' => '#EF4444'],
            ['name' => 'Transporte', 'icon' => 'truck', 'color' => '#F59E0B'],
            ['name' => 'Moradia', 'icon' => 'home', 'color' => '#8B5CF6'],
            ['name' => 'Saúde', 'icon' => 'heart', 'color' => '#EC4899'],
            ['name' => 'Educação', 'icon' => 'book', 'color' => '#3B82F6'],
            ['name' => 'Lazer', 'icon' => 'music', 'color' => '#14B8A6'],
            ['name' => 'Serviços', 'icon' => 'zap', 'color' => '#F59E0B'],
            ['name' => 'Outros', 'icon' => 'more-horizontal', 'color' => '#6B7280'],
        ];

        $category = $type === 'income'
            ? $this->faker->randomElement($incomeCategories)
            : $this->faker->randomElement($expenseCategories);

        return [
            'user_id' => User::factory(),
            'parent_category_id' => null,
            'category_name' => $category['name'],
            'category_type' => $type,
            'icon' => $category['icon'],
            'color' => $category['color'],
            'is_system' => false,
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_type' => 'income',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_type' => 'expense',
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
