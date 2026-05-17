<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('righe_richiesta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('richiesta_id');
            $table->integer('ordinamento')->default(0);
            $table->date('data_servizio');
            $table->time('ora_pickup')->nullable();
            $table->enum('tipo_servizio', ['trasferimento', 'tour', 'esperienza', 'altro'])->default('trasferimento');
            $table->string('pickup');
            $table->string('dropoff');
            $table->integer('passeggeri')->nullable();
            $table->string('veicolo_preferito')->nullable();
            $table->text('note')->nullable();
            $table->json('confidenza')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('richiesta_id')->references('id')->on('richieste')->onDelete('cascade');
            $table->index('richiesta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('righe_richiesta');
    }
};
