<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('telegram_trigger_status_id')
                ->nullable()
                ->after('fuel_supplier_id')
                ->constrained('service_statuses')
                ->nullOnDelete()
                ->comment('Status che attiva invio notifica Telegram ai driver');

            $table->foreignId('telegram_accepted_status_id')
                ->nullable()
                ->after('telegram_trigger_status_id')
                ->constrained('service_statuses')
                ->nullOnDelete()
                ->comment('Status da impostare quando driver accetta via Telegram');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('telegram_trigger_status_id');
            $table->dropConstrainedForeignId('telegram_accepted_status_id');
        });
    }
};
