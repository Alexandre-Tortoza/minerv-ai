<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        Account::factory()
            ->checking()
            ->for($user)
            ->create([
                'account_name' => 'Conta Corrente Principal',
                'institution_name' => 'Banco do Brasil',
                'initial_balance_cents' => 100000,
                'current_balance_cents' => 150000,
                'display_order' => 1,
            ]);

        Account::factory()
            ->savings()
            ->for($user)
            ->create([
                'institution_name' => 'Banco do Brasil',
                'initial_balance_cents' => 500000,
                'current_balance_cents' => 520000,
                'display_order' => 2,
            ]);

        Account::factory()
            ->investment()
            ->for($user)
            ->create([
                'institution_name' => 'XP Investimentos',
                'initial_balance_cents' => 1000000,
                'current_balance_cents' => 1050000,
                'display_order' => 3,
            ]);
    }
}
