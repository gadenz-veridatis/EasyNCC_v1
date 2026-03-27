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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name'); // nome del task (obbligatorio)
            $table->date('due_date')->nullable(); // scadenza
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // assegnatario
            $table->text('notes')->nullable(); // note
            $table->enum('status', ['to_complete', 'completed', 'cancelled'])->default('to_complete'); // stato
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
