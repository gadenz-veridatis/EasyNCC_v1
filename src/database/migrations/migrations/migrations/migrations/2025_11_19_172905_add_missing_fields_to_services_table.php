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
        Schema::table('services', function (Blueprint $table) {
            // Aggiungi campi identificativi mancanti
            $table->string('contact_name')->nullable()->after('passenger_count'); // nominativo di riferimento
            $table->string('contact_phone')->nullable()->after('contact_name'); // contatto di riferimento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_phone']);
        });
    }
};
