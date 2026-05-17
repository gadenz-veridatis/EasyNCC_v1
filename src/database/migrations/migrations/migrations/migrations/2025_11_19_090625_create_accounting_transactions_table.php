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
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->date('transaction_date');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['purchase', 'sale', 'intermediation']); // acquisto, vendita, intermediazione
            $table->foreignId('accounting_entry_id')->nullable()->constrained()->onDelete('set null'); // causale
            $table->enum('installment', ['deposit', 'balance', 'supplier_refund', 'customer_refund']); // acconto, saldo, reso fornitore, rimborso cliente
            $table->foreignId('counterpart_id')->nullable()->constrained('users')->onDelete('set null'); // fornitore/intermediario/committente
            $table->string('document_number')->nullable();
            $table->date('document_due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_type')->nullable(); // bonifico, carta, contanti, altro
            $table->string('iban')->nullable();
            $table->enum('status', ['to_pay', 'paid', 'suspended', 'cancelled', 'to_collect', 'collected']); // stati
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
            $table->index(['service_id']);
            $table->index(['counterpart_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};
