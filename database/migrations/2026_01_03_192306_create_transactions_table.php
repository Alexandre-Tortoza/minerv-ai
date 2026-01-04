<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->enum('transaction_type', ['income', 'expense', 'transfer']);
            $table->bigInteger('amount_cents');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->enum('payment_method', ['cash', 'debit_card', 'credit_card', 'bank_transfer', 'pix', 'other']);
            $table->string('reference_number', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
