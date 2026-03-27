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
            $table->text('activity_confirmation_text')->nullable()->after('balance_reason')->comment('Testo per conferma prenotazione esperienza con segnaposto {$fornitore$} e {$servizio$}');
            $table->string('activity_confirmation_role', 50)->nullable()->after('activity_confirmation_text')->comment('Ruolo utenti a cui assegnare task di conferma esperienza');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['activity_confirmation_text', 'activity_confirmation_role']);
        });
    }
};
