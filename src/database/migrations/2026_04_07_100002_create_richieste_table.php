<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('richieste', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('contact_id');
            $table->enum('fonte', ['email', 'web_form', 'telefono', 'manuale'])->default('manuale');
            $table->enum('stato', [
                'nuova',
                'in_lavorazione',
                'preventivata',
                'confermata',
                'completata',
                'annullata',
                'sospesa',
            ])->default('nuova');
            $table->timestamp('data_ricezione')->nullable();
            $table->text('note')->nullable();
            $table->uuid('origine_id')->nullable();
            $table->enum('relazione', ['sdoppiata_da', 'unita_con'])->nullable();
            $table->foreignId('operatore_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->index('company_id');
            $table->index('contact_id');
            $table->index('stato');
            $table->index('data_ricezione');
        });

        // Self-referencing FK must be added after table creation
        Schema::table('richieste', function (Blueprint $table) {
            $table->foreign('origine_id')->references('id')->on('richieste')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('richieste');
    }
};
