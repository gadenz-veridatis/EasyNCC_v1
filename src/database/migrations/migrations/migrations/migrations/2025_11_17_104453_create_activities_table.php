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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_type_id')->nullable()->constrained('activity_types')->onDelete('set null');
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('cost_per_person', 10, 2)->default(0);
            $table->enum('payment_type', ['INCLUSO', 'CLIENTE', 'AGENZIA', 'NESSUNO'])->default('NESSUNO');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'start_time']);
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
