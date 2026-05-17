<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('richieste', function (Blueprint $table) {
            $table->timestamp('ultimo_messaggio_inbound_at')->nullable()->after('operatore_id');
            $table->timestamp('ultimo_messaggio_letto_at')->nullable()->after('ultimo_messaggio_inbound_at');
        });
    }

    public function down(): void
    {
        Schema::table('richieste', function (Blueprint $table) {
            $table->dropColumn(['ultimo_messaggio_inbound_at', 'ultimo_messaggio_letto_at']);
        });
    }
};
