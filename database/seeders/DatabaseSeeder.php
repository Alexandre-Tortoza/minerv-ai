<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
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

        // Criar uma conta aleatória
        Account::factory()->for($user)->create();

        // Criar conta corrente
        Account::factory()->checking()->for($user)->create();

        // Criar conta poupança ativa
        Account::factory()->savings()->active()->for($user)->create();

        // Criar 5 contas de investimento para um usuário específico
        Account::factory(5)
            ->investment()
            ->for($user)
            ->create();

        // Criar cartão de crédito (sempre com saldo negativo)
        Account::factory()->credit()->for($user)->create();

        // Criar conta inativa
        Account::factory()->inactive()->for($user)->create();

        $this->call([
            AccountsSeeder::class,
        ]);
    }
}
