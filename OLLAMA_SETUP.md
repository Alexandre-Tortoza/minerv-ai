# Chat com IA - Configuração do Ollama

## Pré-requisitos

O chat com IA usa o Ollama rodando localmente para fornecer assistência financeira personalizada.

## Instalação do Ollama

### Linux
```bash
curl -fsSL https://ollama.com/install.sh | sh
```

### macOS
```bash
brew install ollama
```

### Windows
Baixe o instalador em: https://ollama.com/download

## Configuração

1. **Iniciar o Ollama**
```bash
ollama serve
```

2. **Baixar o modelo Llama 3.2**
```bash
ollama pull llama3.2
```

3. **Verificar se está funcionando**
```bash
ollama list
```

Você deve ver o modelo `llama3.2` listado.

## Modelos Alternativos

Se preferir usar outro modelo, edite o arquivo `app/Http/Controllers/ChatController.php` e altere a linha:

```php
'model' => 'llama3.2',
```

Para um dos seguintes modelos:

- `llama3.2` - Recomendado (7B parâmetros)
- `llama3.2:1b` - Mais leve, mais rápido
- `mistral` - Alternativa popular
- `gemma2:2b` - Modelo pequeno do Google

## Uso

1. Acesse a aplicação e faça login
2. Clique em "Chat com IA" no menu lateral
3. Faça perguntas sobre suas finanças, como:
   - "Posso gastar R$ 500 em um jantar este mês?"
   - "Quantas vezes devo parcelar uma compra de R$ 2.000?"
   - "Meu orçamento está equilibrado?"
   - "Como posso economizar mais este mês?"

## Contexto Fornecido à IA

A IA tem acesso aos seguintes dados do usuário:
- Saldo total das contas
- Receitas do mês
- Despesas pagas do mês
- Despesas pendentes
- Principais categorias de gastos

## Problemas Comuns

### Erro: "Erro ao conectar com a IA"
- Verifique se o Ollama está rodando: `ollama serve`
- Verifique se o modelo foi baixado: `ollama list`

### Respostas lentas
- Use um modelo menor como `llama3.2:1b`
- Considere usar uma GPU para acelerar as respostas

### Porta diferente
Se o Ollama estiver rodando em outra porta, edite `ChatController.php`:
```php
Http::post('http://localhost:SUA_PORTA/api/generate', [
```

## URL da API do Ollama

Por padrão, o Ollama roda em: `http://localhost:11434`

A aplicação usa o endpoint: `/api/generate`
