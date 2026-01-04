import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type DashboardProps } from '@/types';
import { Head } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, CreditCard, TrendingUp, Wallet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatCurrency(cents: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
}

export default function Dashboard({ stats, accounts, recent_transactions, expenses_by_category }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Cards de resumo */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Saldo Total</p>
                                <p className="text-2xl font-bold">{formatCurrency(stats.total_balance_cents)}</p>
                            </div>
                            <div className="rounded-full bg-primary/10 p-3">
                                <Wallet className="size-6 text-primary" />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Receitas do Mês</p>
                                <p className="text-2xl font-bold text-green-600">{formatCurrency(stats.monthly_income_cents)}</p>
                            </div>
                            <div className="rounded-full bg-green-500/10 p-3">
                                <ArrowUpRight className="size-6 text-green-600" />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Despesas do Mês</p>
                                <p className="text-2xl font-bold text-red-600">{formatCurrency(stats.monthly_expenses_cents)}</p>
                            </div>
                            <div className="rounded-full bg-red-500/10 p-3">
                                <ArrowDownRight className="size-6 text-red-600" />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Contas */}
                    <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                        <h2 className="mb-4 text-lg font-semibold">Minhas Contas</h2>
                        <div className="space-y-3">
                            {accounts.length > 0 ? (
                                accounts.map((account) => (
                                    <div key={account.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-full bg-primary/10 p-2">
                                                <CreditCard className="size-4 text-primary" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{account.name}</p>
                                                <p className="text-sm text-muted-foreground">{account.institution}</p>
                                            </div>
                                        </div>
                                        <p className="font-semibold">{formatCurrency(account.balance_cents)}</p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-sm text-muted-foreground">Nenhuma conta cadastrada</p>
                            )}
                        </div>
                    </div>

                    {/* Despesas por Categoria */}
                    <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                        <h2 className="mb-4 text-lg font-semibold">Despesas por Categoria</h2>
                        <div className="space-y-3">
                            {expenses_by_category.length > 0 ? (
                                expenses_by_category.map((expense, index) => (
                                    <div key={index} className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div
                                                className="size-3 rounded-full"
                                                style={{ backgroundColor: expense.color }}
                                            />
                                            <p className="text-sm font-medium">{expense.category}</p>
                                        </div>
                                        <p className="text-sm font-semibold">{formatCurrency(expense.amount_cents)}</p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-sm text-muted-foreground">Nenhuma despesa este mês</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Transações Recentes */}
                <div className="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                    <h2 className="mb-4 text-lg font-semibold">Transações Recentes</h2>
                    <div className="space-y-2">
                        {recent_transactions.length > 0 ? (
                            recent_transactions.map((transaction) => (
                                <div
                                    key={transaction.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`rounded-full p-2 ${
                                                transaction.type === 'income'
                                                    ? 'bg-green-500/10'
                                                    : 'bg-red-500/10'
                                            }`}
                                        >
                                            {transaction.type === 'income' ? (
                                                <ArrowUpRight className="size-4 text-green-600" />
                                            ) : (
                                                <ArrowDownRight className="size-4 text-red-600" />
                                            )}
                                        </div>
                                        <div>
                                            <p className="font-medium">{transaction.description}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {transaction.category} • {transaction.date}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p
                                            className={`font-semibold ${
                                                transaction.type === 'income' ? 'text-green-600' : 'text-red-600'
                                            }`}
                                        >
                                            {transaction.type === 'income' ? '+' : '-'}
                                            {formatCurrency(Math.abs(transaction.amount_cents))}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {transaction.is_paid ? 'Pago' : 'Pendente'}
                                        </p>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-center text-sm text-muted-foreground">Nenhuma transação registrada</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
