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
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('default_supplier_id')
                ->nullable()
                ->after('activity_confirmation_role')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Fornitore di default per i servizi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['default_supplier_id']);
            $table->dropColumn('default_supplier_id');
        });
    }
};
