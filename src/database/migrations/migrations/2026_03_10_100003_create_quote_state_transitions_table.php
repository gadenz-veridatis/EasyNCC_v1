<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('from_state', 50)->nullable();
            $table->string('to_state', 50);
            $table->foreignId('transitioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transition_source', 20); // user, webhook, system
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['quote_id', 'created_at']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_state_transitions');
    }
};
