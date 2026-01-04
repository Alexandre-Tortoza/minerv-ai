<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'currency' => 'BRL',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Criar categorias
        $salaryCategory = Category::create([
            'user_id' => $user->id,
            'category_name' => 'Salário',
            'category_type' => 'income',
            'icon' => 'briefcase',
            'color' => '#10B981',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $foodCategory = Category::create([
            'user_id' => $user->id,
            'category_name' => 'Alimentação',
            'category_type' => 'expense',
            'icon' => 'shopping-cart',
            'color' => '#EF4444',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $transportCategory = Category::create([
            'user_id' => $user->id,
            'category_name' => 'Transporte',
            'category_type' => 'expense',
            'icon' => 'truck',
            'color' => '#F59E0B',
            'is_active' => true,
            'display_order' => 2,
        ]);

        $housingCategory = Category::create([
            'user_id' => $user->id,
            'category_name' => 'Moradia',
            'category_type' => 'expense',
            'icon' => 'home',
            'color' => '#8B5CF6',
            'is_active' => true,
            'display_order' => 3,
        ]);

        $this->call([
            AccountsSeeder::class,
        ]);

        $account = Account::where('user_id', $user->id)->first();

        // Criar transações de exemplo
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $salaryCategory->id,
            'transaction_type' => 'income',
            'amount_cents' => 500000,
            'description' => 'Salário Janeiro',
            'transaction_date' => now()->startOfMonth(),
            'is_paid' => true,
            'payment_method' => 'bank_transfer',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $foodCategory->id,
            'transaction_type' => 'expense',
            'amount_cents' => 15000,
            'description' => 'Supermercado',
            'transaction_date' => now()->subDays(2),
            'is_paid' => true,
            'payment_method' => 'debit_card',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $transportCategory->id,
            'transaction_type' => 'expense',
            'amount_cents' => 8000,
            'description' => 'Uber',
            'transaction_date' => now()->subDays(1),
            'is_paid' => true,
            'payment_method' => 'credit_card',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $housingCategory->id,
            'transaction_type' => 'expense',
            'amount_cents' => 120000,
            'description' => 'Aluguel',
            'transaction_date' => now()->startOfMonth(),
            'is_paid' => true,
            'payment_method' => 'bank_transfer',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $foodCategory->id,
            'transaction_type' => 'expense',
            'amount_cents' => 4500,
            'description' => 'Restaurante',
            'transaction_date' => now(),
            'is_paid' => false,
            'payment_method' => 'credit_card',
        ]);
    }
}
