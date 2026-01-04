import AppLayout from '@/layouts/app-layout';
import { chat } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Bot, Send, User, Check, X, Edit2 } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat com IA',
        href: chat().url,
    },
];

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    timestamp: Date;
    pendingAction?: PendingAction;
}

interface PendingAction {
    type: string;
    data: any;
}

export default function Chat() {
    const page = usePage();
    const flash = (page.props as any).flash || {};

    const [messages, setMessages] = useState<Message[]>([
        {
            id: 1,
            role: 'assistant',
            content:
                'Olá! Sou seu assistente financeiro pessoal. Posso ajudar você a tomar decisões sobre gastos, parcelamentos e planejamento financeiro. Como posso ajudar?',
            timestamp: new Date(),
        },
    ]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [editingAction, setEditingAction] = useState<number | null>(null);
    const [editedData, setEditedData] = useState<any>({});
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const lastFlashMessage = useRef<string | null>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        if (flash.success !== undefined && flash.message && flash.message !== lastFlashMessage.current) {
            lastFlashMessage.current = flash.message;
            const aiMessage: Message = {
                id: Date.now(),
                role: 'assistant',
                content: flash.message,
                timestamp: new Date(),
                pendingAction: flash.pendingActions && flash.pendingActions.length > 0
                    ? flash.pendingActions[0]
                    : undefined,
            };
            setMessages((prev) => [...prev, aiMessage]);
            setIsLoading(false);
        }
    }, [flash.success, flash.message]);

    const handleSend = async () => {
        if (!input.trim() || isLoading) return;

        const userMessage: Message = {
            id: Date.now(),
            role: 'user',
            content: input,
            timestamp: new Date(),
        };

        const messageToSend = input;
        setMessages((prev) => [...prev, userMessage]);
        setInput('');
        setIsLoading(true);

        router.post('/chat/send', { message: messageToSend }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Resposta será tratada pelo useEffect do flash
            },
            onError: (errors) => {
                const errorMessage: Message = {
                    id: Date.now(),
                    role: 'assistant',
                    content: 'Erro ao conectar com a IA. Verifique se o Ollama está rodando localmente.',
                    timestamp: new Date(),
                };
                setMessages((prev) => [...prev, errorMessage]);
                setIsLoading(false);
            },
        });
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const handleConfirmAction = (messageId: number, action: PendingAction) => {
        const dataToSend = editingAction === messageId ? editedData : action.data;

        router.post('/chat/confirm-action', {
            type: action.type,
            data: dataToSend,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setMessages((prev) => 
                    prev.map((msg) => 
                        msg.id === messageId 
                            ? { ...msg, pendingAction: undefined }
                            : msg
                    )
                );
                setEditingAction(null);
                setEditedData({});
            },
        });
    };

    const handleCancelAction = (messageId: number) => {
        setMessages((prev) => 
            prev.map((msg) => 
                msg.id === messageId 
                    ? { ...msg, pendingAction: undefined }
                    : msg
            )
        );
        setEditingAction(null);
        setEditedData({});
    };

    const startEditing = (messageId: number, data: any) => {
        setEditingAction(messageId);
        setEditedData({ ...data });
    };

    const renderActionConfirmation = (message: Message) => {
        if (!message.pendingAction) return null;

        const action = message.pendingAction;
        const isEditing = editingAction === message.id;
        const data = isEditing ? editedData : action.data;

        return (
            <div className="mt-3 rounded-lg border-2 border-primary/30 bg-primary/5 p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h4 className="font-semibold text-sm">
                        📋 Confirmação de {action.type === 'ADD_EXPENSE' ? 'Despesa' :
                                          action.type === 'ADD_INCOME' ? 'Receita' :
                                          action.type === 'ADD_BALANCE' ? 'Adição de Saldo' :
                                          'Remoção de Saldo'}
                    </h4>
                    {!isEditing && (
                        <button
                            onClick={() => startEditing(message.id, action.data)}
                            className="text-xs text-primary hover:text-primary/80 flex items-center gap-1"
                        >
                            <Edit2 className="size-3" />
                            Editar
                        </button>
                    )}
                </div>

                <div className="space-y-2 text-sm">
                    {/* ADD_BALANCE ou REMOVE_BALANCE */}
                    {(action.type === 'ADD_BALANCE' || action.type === 'REMOVE_BALANCE') && (
                        <>
                            <div>
                                <span className="text-muted-foreground">Conta:</span>{' '}
                                <span className="font-medium">{data.account_name}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground">Valor:</span>{' '}
                                {isEditing ? (
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.amount}
                                        onChange={(e) => setEditedData({ ...editedData, amount: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm w-32"
                                    />
                                ) : (
                                    <span className="font-medium">R$ {parseFloat(data.amount).toFixed(2)}</span>
                                )}
                            </div>
                            <div>
                                <span className="text-muted-foreground">Descrição:</span>{' '}
                                {isEditing ? (
                                    <input
                                        type="text"
                                        value={data.description}
                                        onChange={(e) => setEditedData({ ...editedData, description: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm flex-1 w-full mt-1"
                                    />
                                ) : (
                                    <span className="font-medium">{data.description}</span>
                                )}
                            </div>
                        </>
                    )}

                    {/* ADD_EXPENSE ou ADD_INCOME */}
                    {(action.type === 'ADD_EXPENSE' || action.type === 'ADD_INCOME') && (
                        <>
                            <div>
                                <span className="text-muted-foreground">Valor:</span>{' '}
                                {isEditing ? (
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.amount}
                                        onChange={(e) => setEditedData({ ...editedData, amount: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm w-32"
                                    />
                                ) : (
                                    <span className="font-medium">R$ {parseFloat(data.amount).toFixed(2)}</span>
                                )}
                            </div>
                            <div>
                                <span className="text-muted-foreground">Descrição:</span>{' '}
                                {isEditing ? (
                                    <input
                                        type="text"
                                        value={data.description}
                                        onChange={(e) => setEditedData({ ...editedData, description: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm flex-1 w-full mt-1"
                                    />
                                ) : (
                                    <span className="font-medium">{data.description}</span>
                                )}
                            </div>
                            <div>
                                <span className="text-muted-foreground">Categoria:</span>{' '}
                                {isEditing ? (
                                    <input
                                        type="text"
                                        value={data.category}
                                        onChange={(e) => setEditedData({ ...editedData, category: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm flex-1 w-full mt-1"
                                    />
                                ) : (
                                    <span className="font-medium">{data.category}</span>
                                )}
                            </div>
                            {isEditing && data.accounts && (
                                <div>
                                    <span className="text-muted-foreground">Conta:</span>{' '}
                                    <select
                                        value={data.account_id || ''}
                                        onChange={(e) => setEditedData({ ...editedData, account_id: e.target.value })}
                                        className="ml-2 rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm w-full mt-1"
                                    >
                                        <option value="">Selecione uma conta</option>
                                        {data.accounts.map((acc: any) => (
                                            <option key={acc.id} value={acc.id}>
                                                {acc.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                        </>
                    )}
                </div>

                <div className="mt-4 flex gap-2">
                    <button
                        onClick={() => handleConfirmAction(message.id, action)}
                        className="flex-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 flex items-center justify-center gap-2"
                    >
                        <Check className="size-4" />
                        Confirmar
                    </button>
                    <button
                        onClick={() => handleCancelAction(message.id)}
                        className="flex-1 rounded-lg border border-sidebar-border/70 bg-background px-4 py-2 text-sm font-medium hover:bg-muted flex items-center justify-center gap-2"
                    >
                        <X className="size-4" />
                        Cancelar
                    </button>
                </div>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat com IA" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                <div className="flex h-full flex-col rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {/* Header */}
                    <div className="border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-3">
                            <div className="rounded-full bg-primary/10 p-2">
                                <Bot className="size-5 text-primary" />
                            </div>
                            <div>
                                <h2 className="font-semibold">Assistente Financeiro IA</h2>
                                <p className="text-sm text-muted-foreground">
                                    Tire suas dúvidas sobre finanças pessoais
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 space-y-4 overflow-y-auto p-4">
                        {messages.map((message) => (
                            <div
                                key={message.id}
                                className={`flex gap-3 ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                            >
                                {message.role === 'assistant' && (
                                    <div className="rounded-full bg-primary/10 p-2">
                                        <Bot className="size-5 text-primary" />
                                    </div>
                                )}
                                <div
                                    className={`max-w-[70%] rounded-lg p-3 ${
                                        message.role === 'user'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border border-sidebar-border/70 bg-muted dark:border-sidebar-border'
                                    }`}
                                >
                                    <div className="prose prose-sm dark:prose-invert max-w-none">
                                        <ReactMarkdown
                                            components={{
                                                p: ({ children }) => <p className="text-sm mb-2 last:mb-0">{children}</p>,
                                                strong: ({ children }) => <strong className="font-semibold">{children}</strong>,
                                                ul: ({ children }) => <ul className="list-disc ml-4 mb-2">{children}</ul>,
                                                ol: ({ children }) => <ol className="list-decimal ml-4 mb-2">{children}</ol>,
                                                li: ({ children }) => <li className="text-sm">{children}</li>,
                                            }}
                                        >
                                            {message.content}
                                        </ReactMarkdown>
                                    </div>

                                    {/* Renderizar confirmação de ação se houver */}
                                    {message.role === 'assistant' && renderActionConfirmation(message)}

                                    <p
                                        className={`mt-1 text-xs ${
                                            message.role === 'user'
                                                ? 'text-primary-foreground/70'
                                                : 'text-muted-foreground'
                                        }`}
                                    >
                                        {message.timestamp.toLocaleTimeString('pt-BR', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </p>
                                </div>
                                {message.role === 'user' && (
                                    <div className="rounded-full bg-primary/10 p-2">
                                        <User className="size-5 text-primary" />
                                    </div>
                                )}
                            </div>
                        ))}
                        {isLoading && (
                            <div className="flex gap-3">
                                <div className="rounded-full bg-primary/10 p-2">
                                    <Bot className="size-5 text-primary" />
                                </div>
                                <div className="max-w-[70%] rounded-lg border border-sidebar-border/70 bg-muted p-3 dark:border-sidebar-border">
                                    <div className="flex gap-1">
                                        <div className="size-2 animate-bounce rounded-full bg-primary" />
                                        <div
                                            className="size-2 animate-bounce rounded-full bg-primary"
                                            style={{ animationDelay: '0.2s' }}
                                        />
                                        <div
                                            className="size-2 animate-bounce rounded-full bg-primary"
                                            style={{ animationDelay: '0.4s' }}
                                        />
                                    </div>
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <div className="border-t border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex gap-2">
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Digite sua pergunta... (Ex: Posso gastar R$ 500 em um restaurante este mês?)"
                                className="flex-1 resize-none rounded-lg border border-sidebar-border/70 bg-background p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-sidebar-border"
                                rows={2}
                                disabled={isLoading}
                            />
                            <button
                                onClick={handleSend}
                                disabled={!input.trim() || isLoading}
                                className="rounded-lg bg-primary px-4 py-2 text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Send className="size-5" />
                            </button>
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            💡 Dica: Pergunte sobre gastos, parcelamentos, orçamento e planejamento financeiro
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
