# Estrutura do Banco de Dados - App Financeiro

## Visão Geral

Sistema de gerenciamento financeiro pessoal com suporte a múltiplas contas, categorização de transações, parcelamentos, despesas recorrentes, orçamentos e análise por IA.

---

## Diagrama de Relacionamentos

```

users (1) ──────────── (N) accounts
│ │
│ │
├─────────────────── (N) categories
│ │
│ │
├─────────────────── (N) transactions ────── (N) installments
│ │
│ │
├─────────────────── (N) recurring_transactions
│ │
│ │
├─────────────────── (N) budgets
│ │
│ │
├─────────────────── (N) financial_goals
│ │
│ │
├─────────────────── (N) financial_snapshots
│ │
│ │
├─────────────────── (N) ai_recommendations
│ │
│ │
├─────────────────── (N) purchase_analysis
│ │
│ │
└─────────────────── (N) mcp_contexts

transactions (1) ──── (1) transactions (self-reference para transferências)
categories (1) ────── (1) categories (self-reference para hierarquia)

```

---

## Tabelas

### 1. users

Gerenciamento de usuários do sistema.

| Coluna            | Tipo         | Descrição               | Constraints                 |
| ----------------- | ------------ | ----------------------- | --------------------------- |
| id                | bigint       | ID único                | PK, auto_increment          |
| name              | varchar(255) | Nome completo           | NOT NULL                    |
| email             | varchar(255) | Email                   | UNIQUE, NOT NULL            |
| email_verified_at | timestamp    | Data verificação email  | nullable                    |
| password          | varchar(255) | Senha hash              | NOT NULL                    |
| currency          | varchar(3)   | Moeda padrão (ISO 4217) | default 'BRL'               |
| timezone          | varchar(50)  | Fuso horário            | default 'America/Sao_Paulo' |
| remember_token    | varchar(100) | Token sessão            | nullable                    |
| created_at        | timestamp    | Data criação            |                             |
| updated_at        | timestamp    | Data atualização        |                             |

**Índices:**

- PRIMARY KEY (id)
- UNIQUE KEY (email)

---

### 2. accounts

Contas bancárias, cartões de crédito e carteiras.

| Coluna                | Tipo         | Descrição                  | Constraints              |
| --------------------- | ------------ | -------------------------- | ------------------------ |
| id                    | bigint       | ID único                   | PK, auto_increment       |
| user_id               | bigint       | ID do usuário              | FK -> users.id, NOT NULL |
| account_name          | varchar(100) | Nome da conta              | NOT NULL                 |
| account_type          | enum         | Tipo de conta              | NOT NULL                 |
| initial_balance_cents | bigint       | Saldo inicial (centavos)   | default 0                |
| current_balance_cents | bigint       | Saldo atual (centavos)     | default 0                |
| currency              | varchar(3)   | Moeda                      | default 'BRL'            |
| institution_name      | varchar(100) | Instituição financeira     | nullable                 |
| is_active             | boolean      | Conta ativa                | default true             |
| display_order         | integer      | Ordem de exibição          | default 0                |
| created_at            | timestamp    | Data criação               |                          |
| updated_at            | timestamp    | Data atualização           |                          |
| deleted_at            | timestamp    | Data deleção (soft delete) | nullable                 |

**Enums:**

- account_type: 'checking', 'savings', 'credit_card', 'cash', 'investment'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, is_active)
- INDEX (account_type)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 3. categories

Categorias de receitas e despesas (com hierarquia).

| Coluna             | Tipo         | Descrição            | Constraints                   |
| ------------------ | ------------ | -------------------- | ----------------------------- |
| id                 | bigint       | ID único             | PK, auto_increment            |
| user_id            | bigint       | ID do usuário        | FK -> users.id, nullable      |
| parent_category_id | bigint       | Categoria pai        | FK -> categories.id, nullable |
| category_name      | varchar(100) | Nome da categoria    | NOT NULL                      |
| category_type      | enum         | Tipo                 | NOT NULL                      |
| icon               | varchar(50)  | Ícone                | nullable                      |
| color              | varchar(7)   | Cor (hex)            | nullable                      |
| is_system          | boolean      | Categoria do sistema | default false                 |
| is_active          | boolean      | Categoria ativa      | default true                  |
| display_order      | integer      | Ordem de exibição    | default 0                     |
| created_at         | timestamp    | Data criação         |                               |
| updated_at         | timestamp    | Data atualização     |                               |
| deleted_at         | timestamp    | Data deleção         | nullable                      |

**Enums:**

- category_type: 'income', 'expense'

**Índices:**

- PRIMARY KEY (id)
- UNIQUE KEY (user_id, category_name, category_type)
- INDEX (user_id, category_type, is_active)
- INDEX (parent_category_id)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- parent_category_id REFERENCES categories(id) ON DELETE CASCADE

---

### 4. installments

Compras parceladas (master record).

| Coluna             | Tipo         | Descrição              | Constraints                   |
| ------------------ | ------------ | ---------------------- | ----------------------------- |
| id                 | bigint       | ID único               | PK, auto_increment            |
| user_id            | bigint       | ID do usuário          | FK -> users.id, NOT NULL      |
| account_id         | bigint       | Conta de débito        | FK -> accounts.id, NOT NULL   |
| category_id        | bigint       | Categoria              | FK -> categories.id, NOT NULL |
| description        | varchar(255) | Descrição da compra    | NOT NULL                      |
| total_amount_cents | bigint       | Valor total (centavos) | NOT NULL                      |
| installment_count  | integer      | Número de parcelas     | NOT NULL                      |
| first_due_date     | date         | Vencimento 1ª parcela  | NOT NULL                      |
| payment_method     | enum         | Forma de pagamento     | NOT NULL                      |
| status             | enum         | Status do parcelamento | default 'active'              |
| created_at         | timestamp    | Data criação           |                               |
| updated_at         | timestamp    | Data atualização       |                               |
| deleted_at         | timestamp    | Data deleção           | nullable                      |

**Enums:**

- payment_method: 'credit_card', 'bank_slip', 'financing', 'other'
- status: 'active', 'completed', 'cancelled'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, status)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- account_id REFERENCES accounts(id) ON DELETE CASCADE
- category_id REFERENCES categories(id) ON DELETE RESTRICT

---

### 5. recurring_transactions

Transações recorrentes (salário, aluguel, assinaturas).

| Coluna                  | Tipo         | Descrição             | Constraints                   |
| ----------------------- | ------------ | --------------------- | ----------------------------- |
| id                      | bigint       | ID único              | PK, auto_increment            |
| user_id                 | bigint       | ID do usuário         | FK -> users.id, NOT NULL      |
| account_id              | bigint       | Conta                 | FK -> accounts.id, NOT NULL   |
| category_id             | bigint       | Categoria             | FK -> categories.id, NOT NULL |
| transaction_type        | enum         | Tipo                  | NOT NULL                      |
| amount_cents            | bigint       | Valor (centavos)      | NOT NULL                      |
| description             | varchar(255) | Descrição             | NOT NULL                      |
| frequency               | enum         | Frequência            | NOT NULL                      |
| frequency_interval      | integer      | Intervalo             | default 1                     |
| start_date              | date         | Data início           | NOT NULL                      |
| end_date                | date         | Data fim              | nullable                      |
| next_occurrence_date    | date         | Próxima ocorrência    | NOT NULL                      |
| payment_method          | enum         | Forma de pagamento    | NOT NULL                      |
| is_active               | boolean      | Ativa                 | default true                  |
| auto_create_transaction | boolean      | Criar automaticamente | default true                  |
| created_at              | timestamp    | Data criação          |                               |
| updated_at              | timestamp    | Data atualização      |                               |
| deleted_at              | timestamp    | Data deleção          | nullable                      |

**Enums:**

- transaction_type: 'income', 'expense'
- frequency: 'daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'
- payment_method: 'cash', 'debit_card', 'credit_card', 'bank_transfer', 'pix', 'other'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, next_occurrence_date, is_active)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- account_id REFERENCES accounts(id) ON DELETE CASCADE
- category_id REFERENCES categories(id) ON DELETE RESTRICT

---

### 6. transactions

Transações financeiras (receitas, despesas, transferências).

| Coluna                   | Tipo         | Descrição                     | Constraints                               |
| ------------------------ | ------------ | ----------------------------- | ----------------------------------------- |
| id                       | bigint       | ID único                      | PK, auto_increment                        |
| user_id                  | bigint       | ID do usuário                 | FK -> users.id, NOT NULL                  |
| account_id               | bigint       | Conta                         | FK -> accounts.id, NOT NULL               |
| category_id              | bigint       | Categoria                     | FK -> categories.id, NOT NULL             |
| transaction_type         | enum         | Tipo                          | NOT NULL                                  |
| amount_cents             | bigint       | Valor (centavos)              | NOT NULL                                  |
| description              | varchar(255) | Descrição                     | NOT NULL                                  |
| notes                    | text         | Observações                   | nullable                                  |
| transaction_date         | date         | Data da transação             | NOT NULL                                  |
| due_date                 | date         | Data de vencimento            | nullable                                  |
| is_paid                  | boolean      | Pago                          | default false                             |
| payment_method           | enum         | Forma de pagamento            | NOT NULL                                  |
| reference_number         | varchar(100) | Número referência             | nullable                                  |
| installment_id           | bigint       | Parcelamento vinculado        | FK -> installments.id, nullable           |
| recurring_transaction_id | bigint       | Recorrência vinculada         | FK -> recurring_transactions.id, nullable |
| transfer_transaction_id  | bigint       | Transação par (transferência) | FK -> transactions.id, nullable           |
| tags                     | json         | Tags                          | nullable                                  |
| created_at               | timestamp    | Data criação                  |                                           |
| updated_at               | timestamp    | Data atualização              |                                           |
| deleted_at               | timestamp    | Data deleção                  | nullable                                  |

**Enums:**

- transaction_type: 'income', 'expense', 'transfer'
- payment_method: 'cash', 'debit_card', 'credit_card', 'bank_transfer', 'pix', 'other'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, transaction_date)
- INDEX (account_id, is_paid)
- INDEX (installment_id)
- INDEX (recurring_transaction_id)
- INDEX (user_id, transaction_date, transaction_type)
- INDEX (account_id, is_paid, transaction_date)
- INDEX (user_id, category_id, transaction_date)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- account_id REFERENCES accounts(id) ON DELETE CASCADE
- category_id REFERENCES categories(id) ON DELETE RESTRICT
- installment_id REFERENCES installments(id) ON DELETE CASCADE
- recurring_transaction_id REFERENCES recurring_transactions(id) ON DELETE SET NULL
- transfer_transaction_id REFERENCES transactions(id) ON DELETE SET NULL

---

### 7. budgets

Orçamentos por categoria e período.

| Coluna              | Tipo         | Descrição               | Constraints                   |
| ------------------- | ------------ | ----------------------- | ----------------------------- |
| id                  | bigint       | ID único                | PK, auto_increment            |
| user_id             | bigint       | ID do usuário           | FK -> users.id, NOT NULL      |
| category_id         | bigint       | Categoria               | FK -> categories.id, nullable |
| budget_name         | varchar(100) | Nome do orçamento       | NOT NULL                      |
| budget_amount_cents | bigint       | Valor limite (centavos) | NOT NULL                      |
| period_type         | enum         | Tipo de período         | NOT NULL                      |
| start_date          | date         | Data início             | NOT NULL                      |
| end_date            | date         | Data fim                | nullable                      |
| is_active           | boolean      | Ativo                   | default true                  |
| alert_percentage    | integer      | % alerta                | default 80                    |
| created_at          | timestamp    | Data criação            |                               |
| updated_at          | timestamp    | Data atualização        |                               |
| deleted_at          | timestamp    | Data deleção            | nullable                      |

**Enums:**

- period_type: 'monthly', 'quarterly', 'yearly'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, period_type, is_active)
- INDEX (category_id)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- category_id REFERENCES categories(id) ON DELETE CASCADE

---

### 8. financial_goals

Metas financeiras (economias, objetivos).

| Coluna               | Tipo         | Descrição              | Constraints              |
| -------------------- | ------------ | ---------------------- | ------------------------ |
| id                   | bigint       | ID único               | PK, auto_increment       |
| user_id              | bigint       | ID do usuário          | FK -> users.id, NOT NULL |
| goal_name            | varchar(100) | Nome da meta           | NOT NULL                 |
| target_amount_cents  | bigint       | Valor alvo (centavos)  | NOT NULL                 |
| current_amount_cents | bigint       | Valor atual (centavos) | default 0                |
| deadline_date        | date         | Data limite            | nullable                 |
| priority             | enum         | Prioridade             | default 'medium'         |
| status               | enum         | Status                 | default 'active'         |
| icon                 | varchar(50)  | Ícone                  | nullable                 |
| notes                | text         | Observações            | nullable                 |
| created_at           | timestamp    | Data criação           |                          |
| updated_at           | timestamp    | Data atualização       |                          |
| deleted_at           | timestamp    | Data deleção           | nullable                 |

**Enums:**

- priority: 'low', 'medium', 'high'
- status: 'active', 'achieved', 'cancelled'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, status)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 9. financial_snapshots

Fotos mensais do estado financeiro para análise.

| Coluna                 | Tipo         | Descrição                | Constraints              |
| ---------------------- | ------------ | ------------------------ | ------------------------ |
| id                     | bigint       | ID único                 | PK, auto_increment       |
| user_id                | bigint       | ID do usuário            | FK -> users.id, NOT NULL |
| snapshot_date          | date         | Data referência          | NOT NULL                 |
| total_income_cents     | bigint       | Receita total (centavos) | NOT NULL                 |
| total_expenses_cents   | bigint       | Despesa total (centavos) | NOT NULL                 |
| total_balance_cents    | bigint       | Saldo total (centavos)   | NOT NULL                 |
| savings_rate           | decimal(5,2) | Taxa de economia (%)     | NOT NULL                 |
| top_expense_categories | json         | Top categorias despesa   | nullable                 |
| spending_score         | integer      | Score gasto (0-100)      | nullable                 |
| metadata               | json         | Metadados adicionais     | nullable                 |
| created_at             | timestamp    | Data criação             |                          |
| updated_at             | timestamp    | Data atualização         |                          |

**Índices:**

- PRIMARY KEY (id)
- UNIQUE KEY (user_id, snapshot_date)
- INDEX (user_id, snapshot_date DESC)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 10. ai_recommendations

Recomendações geradas pela IA.

| Coluna                  | Tipo         | Descrição           | Constraints              |
| ----------------------- | ------------ | ------------------- | ------------------------ |
| id                      | bigint       | ID único            | PK, auto_increment       |
| user_id                 | bigint       | ID do usuário       | FK -> users.id, NOT NULL |
| recommendation_type     | enum         | Tipo recomendação   | NOT NULL                 |
| title                   | varchar(255) | Título              | NOT NULL                 |
| description             | text         | Descrição detalhada | NOT NULL                 |
| priority                | enum         | Prioridade          | default 'medium'         |
| potential_savings_cents | bigint       | Economia potencial  | nullable                 |
| is_read                 | boolean      | Lida                | default false            |
| is_accepted             | boolean      | Aceita              | nullable                 |
| action_taken_at         | timestamp    | Data ação           | nullable                 |
| expires_at              | timestamp    | Data expiração      | nullable                 |
| metadata                | json         | Dados contextuais   | nullable                 |
| created_at              | timestamp    | Data criação        |                          |
| updated_at              | timestamp    | Data atualização    |                          |

**Enums:**

- recommendation_type: 'budget_adjustment', 'savings_opportunity', 'spending_warning', 'investment_suggestion', 'debt_payoff'
- priority: 'low', 'medium', 'high', 'urgent'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, is_read, priority)
- INDEX (user_id, recommendation_type)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 11. purchase_analysis

Análise de capacidade de compra.

| Coluna                   | Tipo      | Descrição                   | Constraints                   |
| ------------------------ | --------- | --------------------------- | ----------------------------- |
| id                       | bigint    | ID único                    | PK, auto_increment            |
| user_id                  | bigint    | ID do usuário               | FK -> users.id, NOT NULL      |
| requested_amount_cents   | bigint    | Valor solicitado (centavos) | NOT NULL                      |
| requested_installments   | integer   | Parcelas solicitadas        | nullable                      |
| category_id              | bigint    | Categoria                   | FK -> categories.id, nullable |
| analysis_result          | enum      | Resultado análise           | NOT NULL                      |
| affordability_score      | integer   | Score capacidade (0-100)    | NOT NULL                      |
| recommended_installments | integer   | Parcelas recomendadas       | nullable                      |
| recommended_amount_cents | bigint    | Valor recomendado           | nullable                      |
| impact_on_budget         | json      | Impacto no orçamento        | NOT NULL                      |
| reasoning                | text      | Justificativa               | NOT NULL                      |
| alternatives             | json      | Alternativas sugeridas      | nullable                      |
| expires_at               | timestamp | Data expiração              | NOT NULL                      |
| created_at               | timestamp | Data criação                |                               |

**Enums:**

- analysis_result: 'approved', 'approved_with_conditions', 'not_recommended', 'denied'

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, created_at)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE
- category_id REFERENCES categories(id) ON DELETE SET NULL

---

### 12. attachments

Anexos para transações e parcelamentos (notas fiscais, comprovantes).

| Coluna          | Tipo         | Descrição                   | Constraints              |
| --------------- | ------------ | --------------------------- | ------------------------ |
| id              | bigint       | ID único                    | PK, auto_increment       |
| user_id         | bigint       | ID do usuário               | FK -> users.id, NOT NULL |
| attachable_type | varchar(100) | Tipo entidade (polymorphic) | NOT NULL                 |
| attachable_id   | bigint       | ID entidade                 | NOT NULL                 |
| file_name       | varchar(255) | Nome arquivo                | NOT NULL                 |
| file_path       | varchar(500) | Caminho arquivo             | NOT NULL                 |
| file_type       | varchar(50)  | Tipo MIME                   | NOT NULL                 |
| file_size       | integer      | Tamanho (bytes)             | NOT NULL                 |
| created_at      | timestamp    | Data upload                 |                          |
| updated_at      | timestamp    | Data atualização            |                          |

**Índices:**

- PRIMARY KEY (id)
- INDEX (attachable_type, attachable_id)
- INDEX (user_id)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 13. mcp_contexts

Contextos de conversação com IA via MCP.

| Coluna       | Tipo         | Descrição        | Constraints              |
| ------------ | ------------ | ---------------- | ------------------------ |
| id           | bigint       | ID único         | PK, auto_increment       |
| user_id      | bigint       | ID do usuário    | FK -> users.id, NOT NULL |
| context_key  | varchar(100) | Chave contexto   | NOT NULL                 |
| context_data | json         | Dados contexto   | NOT NULL                 |
| expires_at   | timestamp    | Data expiração   | nullable                 |
| created_at   | timestamp    | Data criação     |                          |
| updated_at   | timestamp    | Data atualização |                          |

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id, context_key)

**Foreign Keys:**

- user_id REFERENCES users(id) ON DELETE CASCADE

---

### 14. password_reset_tokens

Tokens para reset de senha (padrão Laravel).

| Coluna     | Tipo         | Descrição    | Constraints |
| ---------- | ------------ | ------------ | ----------- |
| email      | varchar(255) | Email        | PK          |
| token      | varchar(255) | Token hash   | NOT NULL    |
| created_at | timestamp    | Data criação | nullable    |

**Índices:**

- PRIMARY KEY (email)

---

### 15. sessions

Sessões de usuário (padrão Laravel).

| Coluna        | Tipo         | Descrição           | Constraints              |
| ------------- | ------------ | ------------------- | ------------------------ |
| id            | varchar(255) | ID sessão           | PK                       |
| user_id       | bigint       | ID usuário          | FK -> users.id, nullable |
| ip_address    | varchar(45)  | IP                  | nullable                 |
| user_agent    | text         | User agent          | nullable                 |
| payload       | longtext     | Dados sessão        | NOT NULL                 |
| last_activity | integer      | Timestamp atividade | NOT NULL                 |

**Índices:**

- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (last_activity)

---

## Convenções de Nomenclatura

### Tabelas

- Plural, snake_case: `users`, `transactions`, `financial_goals`

### Colunas

- snake_case: `user_id`, `account_name`, `created_at`
- Valores monetários: sufixo `_cents` (armazenados como INTEGER)
- Datas: sufixo `_date` ou `_at`
- Booleanos: prefixo `is_` ou `has_`

### Foreign Keys

- Formato: `{tabela_singular}_id`
- Exemplo: `user_id`, `account_id`, `category_id`

### Índices

- Simples: nome da coluna
- Compostos: colunas mais usadas em queries primeiro
- Únicos: colunas ou combinações que garantem unicidade

---

## Regras de Integridade Referencial

### CASCADE ON DELETE

- `accounts` → `users`
- `transactions` → `users`, `accounts`, `installments`
- `categories` → `users`, `parent_category_id`
- `budgets` → `users`, `category_id`
- Todas tabelas de análise IA → `users`

### RESTRICT ON DELETE

- `transactions` → `categories` (não permite deletar categoria em uso)
- `installments` → `categories`
- `recurring_transactions` → `categories`

### SET NULL ON DELETE

- `transactions` → `recurring_transaction_id`, `transfer_transaction_id`
- `purchase_analysis` → `category_id`

---

## Observações Importantes

### Valores Monetários

- **Todos** valores monetários são armazenados como `bigint` em **centavos**
- Exemplo: R$ 100,50 = 10050
- Razão: SQLite não garante precisão decimal exata

### Soft Deletes

- Tabelas principais usam `deleted_at` (timestamp nullable)
- Permite recuperação de dados e mantém histórico
- Tabelas com soft delete: `accounts`, `categories`, `transactions`, `installments`, `recurring_transactions`, `budgets`, `financial_goals`

### JSON Columns

- `tags` em transactions
- `top_expense_categories`, `metadata` em financial_snapshots
- `impact_on_budget`, `alternatives` em purchase_analysis
- `context_data` em mcp_contexts

### Self-References

- `categories.parent_category_id` → `categories.id` (hierarquia)
- `transactions.transfer_transaction_id` → `transactions.id` (transferências entre contas)

### Polymorphic Relations

- `attachments.attachable_type` e `attachable_id` permitem anexar arquivos em qualquer entidade

---

## Ordem de Criação das Migrations

1. `users`
2. `password_reset_tokens`
3. `sessions`
4. `accounts`
5. `categories`
6. `installments`
7. `recurring_transactions`
8. `transactions` (depende de todas acima)
9. `budgets`
10. `financial_goals`
11. `financial_snapshots`
12. `ai_recommendations`
13. `purchase_analysis`
14. `attachments`
15. `mcp_contexts`

---

## Queries Críticas para Performance

### Índices Essenciais para IA

```sql
-- Padrões de gasto
INDEX (user_id, category_id, transaction_date)

-- Fluxo de caixa
INDEX (account_id, is_paid, transaction_date)

-- Análise temporal
INDEX (user_id, transaction_date DESC)

-- Snapshots mensais
INDEX (user_id, snapshot_date DESC)
```

### Estimativa de Tamanho

Para 1 usuário com 1 ano de dados:

- Transações: ~500-1000 registros
- Snapshots: 12 registros
- Recomendações: 20-50 registros
- Total estimado: ~10-50 MB

---

## Integração MCP (Model Context Protocol)

### Recursos Expostos

- `mcp://financial/user/{user_id}/summary`
- `mcp://financial/user/{user_id}/transactions`
- `mcp://financial/user/{user_id}/budgets`
- `mcp://financial/user/{user_id}/goals`

### Tools Disponíveis

- `analyze_spending_patterns`
- `calculate_affordability`
- `suggest_budget_adjustments`
- `predict_future_balance`

---

## Considerações de Segurança

1. **Criptografia**: Valores monetários e attachments devem usar criptografia em repouso
2. **Rate Limiting**: Limitar análises de IA por hora/dia
3. **Audit Log**: Considerar tabela separada para auditoria
4. **LGPD**: Soft deletes permitem anonimização mantendo integridade

---

## Escalabilidade Futura

### Particionamento

- `transactions` por ano quando volume > 100k registros
- `financial_snapshots` por ano

### Cache

- Dashboard agregado (Redis)
- Saldo de contas (Redis com TTL curto)

### Read Replicas

- Relatórios e análises em replica
- Writes apenas em master

```

```
