<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('righe_richiesta', function (Blueprint $table) {
            $table->date('data_servizio')->nullable()->change();
            $table->string('pickup')->nullable()->change();
            $table->string('dropoff')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('righe_richiesta', function (Blueprint $table) {
            $table->date('data_servizio')->nullable(false)->change();
            $table->string('pickup')->nullable(false)->change();
            $table->string('dropoff')->nullable(false)->change();
        });
    }
};
