import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Account {
    id: number;
    name: string;
    type: 'checking' | 'savings' | 'credit_card' | 'cash' | 'investment';
    balance_cents: number;
    institution: string;
}

export interface Transaction {
    id: number;
    description: string;
    amount_cents: number;
    type: 'income' | 'expense' | 'transfer';
    date: string;
    account: string | null;
    category: string | null;
    is_paid: boolean;
}

export interface ExpenseByCategory {
    category: string;
    amount_cents: number;
    color: string;
}

export interface DashboardStats {
    total_balance_cents: number;
    monthly_income_cents: number;
    monthly_expenses_cents: number;
    monthly_balance_cents: number;
}

export interface DashboardProps {
    stats: DashboardStats;
    accounts: Account[];
    recent_transactions: Transaction[];
    expenses_by_category: ExpenseByCategory[];
}
