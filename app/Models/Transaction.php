<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'transaction_type',
        'amount_cents',
        'description',
        'notes',
        'transaction_date',
        'due_date',
        'is_paid',
        'payment_method',
        'reference_number',
        'installment_id',
        'recurring_transaction_id',
        'transfer_transaction_id',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'due_date' => 'date',
            'is_paid' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function recurringTransaction()
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function transferTransaction()
    {
        return $this->belongsTo(Transaction::class, 'transfer_transaction_id');
    }
}
