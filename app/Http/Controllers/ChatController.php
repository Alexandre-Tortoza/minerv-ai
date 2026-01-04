<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        return Inertia::render('chat');
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = auth()->user();

        // Obter contexto financeiro do usuário
        $context = $this->getFinancialContext($user);
        $systemPrompt = "Você é um assistente financeiro pessoal especializado em matemática financeira e planejamento de gastos.

Você tem acesso às seguintes FERRAMENTAS que pode usar quando o usuário pedir:

1. ADICIONAR_SALDO: Adiciona dinheiro a uma conta
   Formato: [ACTION:ADD_BALANCE|account_id|amount|description]
   Exemplo: [ACTION:ADD_BALANCE|1|500.00|Salário recebido]

2. REMOVER_SALDO: Remove dinheiro de uma conta
   Formato: [ACTION:REMOVE_BALANCE|account_id|amount|description]
   Exemplo: [ACTION:REMOVE_BALANCE|1|200.00|Saque emergencial]

3. ADICIONAR_DESPESA: Cria uma nova despesa
   Formato: [ACTION:ADD_EXPENSE|amount|description|category]
   Exemplo: [ACTION:ADD_EXPENSE|150.00|Supermercado|Alimentação]

4. ADICIONAR_RECEITA: Cria uma nova receita
   Formato: [ACTION:ADD_INCOME|amount|description|category]
   Exemplo: [ACTION:ADD_INCOME|5000.00|Salário|Salário]

Contexto Financeiro do Usuário:
{$context}

IMPORTANTE:
- Quando o usuário pedir para ADICIONAR, REMOVER, CRIAR uma transação, use a ferramenta apropriada
- SEMPRE coloque a ação em uma linha separada NO FINAL da sua resposta
- Explique o que vai fazer ANTES de executar a ação
- Seja preciso com os números
- Dê respostas claras, objetivas e amigáveis em português do Brasil";

        try {
            // Chamar API do Ollama
            $response = Http::timeout(60)->post('http://localhost:11434/api/generate', [
                'model' => 'gemma3:12b',
                'prompt' => $systemPrompt."\n\nUsuário: ".$request->message."\n\nAssistente:",
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                    'num_predict' => 400,
                ],
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json('response');

                // Processar ações se houver
                $result = $this->processActions($aiResponse, $user);

                return redirect()->back()->with([
                    'success' => true,
                    'message' => $result['message'],
                    'pendingActions' => $result['pendingActions'],
                ]);
            }

            return redirect()->back()->with([
                'success' => false,
                'message' => 'Erro ao conectar com a IA. Verifique se o Ollama está rodando.',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'success' => false,
                'message' => 'Erro ao conectar com a IA: '.$e->getMessage(),
            ]);
        }
    }

    private function getFinancialContext($user)
    {
        $currentMonth = now()->startOfMonth();
        $today = now();
        $daysInMonth = $today->daysInMonth;
        $daysPassed = $today->day;
        $daysRemaining = $daysInMonth - $daysPassed;

        // Saldo total
        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
        $totalBalance = $accounts->sum('current_balance_cents');

        // Transações do mês
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

        $pendingExpenses = $transactions
            ->where('transaction_type', 'expense')
            ->where('is_paid', false)
            ->sum('amount_cents');

        $availableBalance = $totalBalance - $pendingExpenses;

        // Despesas por categoria
        $expensesByCategory = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'expense')
            ->where('transaction_date', '>=', $currentMonth)
            ->where('is_paid', true)
            ->with('category')
            ->get()
            ->groupBy('category.category_name')
            ->map(fn ($items) => $items->sum('amount_cents'))
            ->sortDesc()
            ->take(5);

        // Média de gastos diários até agora
        $dailyAverageSpent = $daysPassed > 0 ? $monthlyExpenses / $daysPassed : 0;

        $context = 'DATA ATUAL: '.$today->format('d/m/Y')." (Dia {$daysPassed} de {$daysInMonth})\n";
        $context .= "DIAS RESTANTES NO MÊS: {$daysRemaining} dias\n\n";

        // Listar contas disponíveis
        $context .= "CONTAS DISPONÍVEIS:\n";
        foreach ($accounts as $account) {
            $context .= "- ID: {$account->id} | {$account->account_name}: R$ ".number_format($account->current_balance_cents / 100, 2, ',', '.')."\n";
        }
        $context .= "\n";

        $context .= 'SALDO TOTAL: R$ '.number_format($totalBalance / 100, 2, ',', '.')."\n";
        $context .= 'DESPESAS PENDENTES: R$ '.number_format($pendingExpenses / 100, 2, ',', '.')."\n";
        $context .= 'SALDO DISPONÍVEL: R$ '.number_format($availableBalance / 100, 2, ',', '.')."\n\n";
        $context .= 'RECEITAS DO MÊS: R$ '.number_format($monthlyIncome / 100, 2, ',', '.')."\n";
        $context .= 'DESPESAS PAGAS DO MÊS: R$ '.number_format($monthlyExpenses / 100, 2, ',', '.')."\n";
        $context .= 'MÉDIA DE GASTO DIÁRIO: R$ '.number_format($dailyAverageSpent / 100, 2, ',', '.')."/dia\n";

        if ($expensesByCategory->isNotEmpty()) {
            $context .= "\nPRINCIPAIS CATEGORIAS DE DESPESA:\n";
            foreach ($expensesByCategory as $category => $amount) {
                $context .= "- {$category}: R$ ".number_format($amount / 100, 2, ',', '.')."\n";
            }
        }

        return $context;
    }

    private function processActions($response, $user)
    {
        // Detectar ações no formato [ACTION:TYPE|param1|param2|...]
        if (preg_match_all('/\[ACTION:([^\]]+)\]/', $response, $matches)) {
            $pendingActions = [];

            foreach ($matches[1] as $actionString) {
                $parts = explode('|', $actionString);
                $actionType = $parts[0];

                // Preparar dados da ação para confirmação (NÃO executa ainda)
                switch ($actionType) {
                    case 'ADD_BALANCE':
                        $accountId = $parts[1] ?? null;
                        $account = Account::where('user_id', $user->id)->where('id', $accountId)->first();
                        $pendingActions[] = [
                            'type' => 'ADD_BALANCE',
                            'data' => [
                                'account_id' => $accountId,
                                'account_name' => $account?->account_name ?? 'Conta não encontrada',
                                'amount' => $parts[2] ?? 0,
                                'description' => $parts[3] ?? 'Adição de saldo',
                            ],
                        ];
                        break;

                    case 'REMOVE_BALANCE':
                        $accountId = $parts[1] ?? null;
                        $account = Account::where('user_id', $user->id)->where('id', $accountId)->first();
                        $pendingActions[] = [
                            'type' => 'REMOVE_BALANCE',
                            'data' => [
                                'account_id' => $accountId,
                                'account_name' => $account?->account_name ?? 'Conta não encontrada',
                                'amount' => $parts[2] ?? 0,
                                'description' => $parts[3] ?? 'Remoção de saldo',
                            ],
                        ];
                        break;

                    case 'ADD_EXPENSE':
                        $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();
                        $pendingActions[] = [
                            'type' => 'ADD_EXPENSE',
                            'data' => [
                                'amount' => $parts[1] ?? 0,
                                'description' => $parts[2] ?? 'Despesa',
                                'category' => $parts[3] ?? 'Outras',
                                'accounts' => $accounts->map(fn ($acc) => [
                                    'id' => $acc->id,
                                    'name' => $acc->account_name,
                                ])->toArray(),
                            ],
                        ];
                        break;

                    case 'ADD_INCOME':
                        $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();
                        $pendingActions[] = [
                            'type' => 'ADD_INCOME',
                            'data' => [
                                'amount' => $parts[1] ?? 0,
                                'description' => $parts[2] ?? 'Receita',
                                'category' => $parts[3] ?? 'Salário',
                                'accounts' => $accounts->map(fn ($acc) => [
                                    'id' => $acc->id,
                                    'name' => $acc->account_name,
                                ])->toArray(),
                            ],
                        ];
                        break;
                }
            }

            // Remover os marcadores de ação da resposta
            $cleanResponse = preg_replace('/\[ACTION:[^\]]+\]\s*/', '', $response);

            // Retornar resposta com ações pendentes
            return [
                'message' => $cleanResponse,
                'pendingActions' => $pendingActions,
            ];
        }

        return ['message' => $response, 'pendingActions' => []];
    }

    public function confirmAction(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'data' => 'required|array',
        ]);

        $user = auth()->user();
        $type = $request->type;
        $data = $request->data;

        try {
            switch ($type) {
                case 'ADD_BALANCE':
                    $result = $this->addBalance(
                        $user,
                        $data['account_id'],
                        $data['amount'],
                        $data['description']
                    );
                    break;

                case 'REMOVE_BALANCE':
                    $result = $this->removeBalance(
                        $user,
                        $data['account_id'],
                        $data['amount'],
                        $data['description']
                    );
                    break;

                case 'ADD_EXPENSE':
                    $result = $this->addExpense(
                        $user,
                        $data['amount'],
                        $data['description'],
                        $data['category'],
                        $data['account_id'] ?? null
                    );
                    break;

                case 'ADD_INCOME':
                    $result = $this->addIncome(
                        $user,
                        $data['amount'],
                        $data['description'],
                        $data['category'],
                        $data['account_id'] ?? null
                    );
                    break;

                default:
                    throw new \Exception('Tipo de ação inválido');
            }

            return redirect()->back()->with([
                'success' => true,
                'message' => "✅ {$result}",
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'success' => false,
                'message' => '❌ Erro ao executar ação: '.$e->getMessage(),
            ]);
        }
    }

    private function addBalance($user, $accountId, $amount, $description)
    {
        $amountCents = (float) $amount * 100;

        $account = Account::where('user_id', $user->id)
            ->where('id', $accountId)
            ->firstOrFail();

        $account->current_balance_cents += $amountCents;
        $account->save();

        // Criar transação de receita
        $category = Category::where('user_id', $user->id)
            ->where('category_type', 'income')
            ->first();

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id ?? null,
            'transaction_type' => 'income',
            'amount_cents' => $amountCents,
            'description' => $description,
            'transaction_date' => now(),
            'is_paid' => true,
            'payment_method' => 'pix',
        ]);

        return 'Saldo adicionado: R$ '.number_format($amount, 2, ',', '.')." em {$account->account_name}. Novo saldo: R$ ".number_format($account->current_balance_cents / 100, 2, ',', '.');
    }

    private function removeBalance($user, $accountId, $amount, $description)
    {
        $amountCents = (float) $amount * 100;

        $account = Account::where('user_id', $user->id)
            ->where('id', $accountId)
            ->firstOrFail();

        $account->current_balance_cents -= $amountCents;
        $account->save();

        // Criar transação de despesa
        $category = Category::where('user_id', $user->id)
            ->where('category_type', 'expense')
            ->first();

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id ?? null,
            'transaction_type' => 'expense',
            'amount_cents' => $amountCents,
            'description' => $description,
            'transaction_date' => now(),
            'is_paid' => true,
            'payment_method' => 'pix',
        ]);

        return 'Saldo removido: R$ '.number_format($amount, 2, ',', '.')." de {$account->account_name}. Novo saldo: R$ ".number_format($account->current_balance_cents / 100, 2, ',', '.');
    }

    private function addExpense($user, $amount, $description, $categoryName, $accountId = null)
    {
        $amountCents = (float) $amount * 100;

        // Buscar ou criar categoria
        $category = Category::firstOrCreate([
            'user_id' => $user->id,
            'category_name' => $categoryName,
            'category_type' => 'expense',
        ], [
            'icon' => 'circle-dollar-sign',
            'color' => '#ef4444',
        ]);

        // Buscar conta especificada ou primeira conta ativa
        if ($accountId) {
            $account = Account::where('user_id', $user->id)
                ->where('id', $accountId)
                ->firstOrFail();
        } else {
            $account = Account::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw new \Exception('Nenhuma conta ativa encontrada');
            }
        }

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_type' => 'expense',
            'amount_cents' => $amountCents,
            'description' => $description,
            'transaction_date' => now(),
            'is_paid' => true,
            'payment_method' => 'pix',
        ]);

        // Atualizar saldo da conta
        $account->current_balance_cents -= $amountCents;
        $account->save();

        return 'Despesa adicionada: R$ '.number_format($amount, 2, ',', '.')." - {$description} ({$categoryName})";
    }

    private function addIncome($user, $amount, $description, $categoryName, $accountId = null)
    {
        $amountCents = (float) $amount * 100;

        // Buscar ou criar categoria
        $category = Category::firstOrCreate([
            'user_id' => $user->id,
            'category_name' => $categoryName,
            'category_type' => 'income',
        ], [
            'icon' => 'trending-up',
            'color' => '#22c55e',
        ]);

        // Buscar conta especificada ou primeira conta ativa
        if ($accountId) {
            $account = Account::where('user_id', $user->id)
                ->where('id', $accountId)
                ->firstOrFail();
        } else {
            $account = Account::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw new \Exception('Nenhuma conta ativa encontrada');
            }
        }

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_type' => 'income',
            'amount_cents' => $amountCents,
            'description' => $description,
            'transaction_date' => now(),
            'is_paid' => true,
            'payment_method' => 'pix',
        ]);

        // Atualizar saldo da conta
        $account->current_balance_cents += $amountCents;
        $account->save();

        return 'Receita adicionada: R$ '.number_format($amount, 2, ',', '.')." - {$description} ({$categoryName})";
    }
}
