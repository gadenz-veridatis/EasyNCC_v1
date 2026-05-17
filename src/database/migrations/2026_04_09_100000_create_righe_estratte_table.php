<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('righe_estratte', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('thread_email_id');
            $table->integer('ordinamento')->default(0);
            $table->date('data_servizio')->nullable();
            $table->time('ora_pickup')->nullable();
            $table->enum('tipo_servizio', ['trasferimento', 'tour', 'esperienza', 'altro'])->default('altro');
            $table->string('pickup')->nullable();
            $table->string('dropoff')->nullable();
            $table->integer('passeggeri')->nullable();
            $table->string('veicolo_preferito')->nullable();
            $table->text('note')->nullable();
            $table->json('confidenza')->nullable();
            $table->uuid('riga_richiesta_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('thread_email_id')->references('id')->on('thread_emails')->onDelete('cascade');
            $table->foreign('riga_richiesta_id')->references('id')->on('righe_richiesta')->onDelete('set null');
            $table->index('thread_email_id');
        });

        // Add traceability to righe_richiesta
        Schema::table('righe_richiesta', function (Blueprint $table) {
            $table->uuid('riga_estratta_origine_id')->nullable()->after('confidenza');
            $table->boolean('modificata_manualmente')->default(false)->after('riga_estratta_origine_id');

            $table->foreign('riga_estratta_origine_id')->references('id')->on('righe_estratte')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('righe_richiesta', function (Blueprint $table) {
            $table->dropForeign(['riga_estratta_origine_id']);
            $table->dropColumn(['riga_estratta_origine_id', 'modificata_manualmente']);
        });

        Schema::dropIfExists('righe_estratte');
    }
};
