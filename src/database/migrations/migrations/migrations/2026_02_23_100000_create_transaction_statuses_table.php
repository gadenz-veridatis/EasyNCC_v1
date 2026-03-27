<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the transaction_statuses dictionary table
        Schema::create('transaction_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');                  // Display name (e.g. "Da Pagare")
            $table->string('code', 50);              // Slug code (e.g. "to_pay")
            $table->string('abbreviation', 20)->nullable(); // Short form (e.g. "DP")
            $table->string('color', 50)->nullable(); // Badge CSS class suffix (e.g. "warning", "success")
            $table->string('transaction_type_group', 20)->default('both'); // "purchase", "sale", or "both"
            $table->boolean('is_final')->default(false); // If true, blocks automatic procedure updates
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'transaction_type_group']);
        });

        // 2. Alter accounting_transactions.status from enum to varchar
        // PostgreSQL requires dropping the enum constraint and changing the column type
        DB::statement("ALTER TABLE accounting_transactions ALTER COLUMN status TYPE VARCHAR(50) USING status::VARCHAR(50)");

        // Drop the old enum type if it exists
        DB::statement("DROP TYPE IF EXISTS accounting_transactions_status_check");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore enum type
        DB::statement("ALTER TABLE accounting_transactions ALTER COLUMN status TYPE VARCHAR(50)");

        Schema::dropIfExists('transaction_statuses');
    }
};
