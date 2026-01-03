<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Resumo de contas
        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $totalBalance = $accounts->sum('current_balance_cents');

        // Transações do mês atual
        $currentMonth = now()->startOfMonth();
        $transactions = Transaction::where('user_id', $user->id)
            ->where('transaction_date', '>=', $currentMonth)
            ->get();

        $monthlyIncome = $transactions
            ->where('transaction_type', 'income')
            ->where('is_paid', true)
            ->sum('amount_cents');

        $monthlyExpenses = $transactions
            ->where('transaction_type', 'expense')
            ->where('is_paid', true)
            ->sum('amount_cents');

        // Transações recentes
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['account', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount_cents' => $transaction->amount_cents,
                    'type' => $transaction->transaction_type,
                    'date' => $transaction->transaction_date->format('d/m/Y'),
                    'account' => $transaction->account->account_name ?? null,
                    'category' => $transaction->category->category_name ?? null,
                    'is_paid' => $transaction->is_paid,
                ];
            });

        // Despesas por categoria (top 5)
        $expensesByCategory = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'expense')
            ->where('transaction_date', '>=', $currentMonth)
            ->where('is_paid', true)
            ->select('category_id', DB::raw('SUM(amount_cents) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->category_name ?? 'Sem categoria',
                    'amount_cents' => $item->total,
                    'color' => $item->category->color ?? '#6B7280',
                ];
            });

        return Inertia::render('dashboard', [
            'stats' => [
                'total_balance_cents' => $totalBalance,
                'monthly_income_cents' => $monthlyIncome,
                'monthly_expenses_cents' => $monthlyExpenses,
                'monthly_balance_cents' => $monthlyIncome - $monthlyExpenses,
            ],
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'type' => $account->account_type,
                    'balance_cents' => $account->current_balance_cents,
                    'institution' => $account->institution_name,
                ];
            }),
            'recent_transactions' => $recentTransactions,
            'expenses_by_category' => $expensesByCategory,
        ]);
    }
}
