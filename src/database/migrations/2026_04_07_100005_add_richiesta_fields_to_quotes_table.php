<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('richiesta_id')->nullable()->after('company_id');
            $table->date('scadenza')->nullable()->after('deposit_received_at');

            $table->foreign('richiesta_id')->references('id')->on('richieste')->onDelete('set null');
            $table->index('richiesta_id');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->uuid('riga_richiesta_id')->nullable()->after('quote_id');

            $table->foreign('riga_richiesta_id')->references('id')->on('righe_richiesta')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropForeign(['riga_richiesta_id']);
            $table->dropColumn('riga_richiesta_id');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['richiesta_id']);
            $table->dropIndex(['richiesta_id']);
            $table->dropColumn(['richiesta_id', 'scadenza']);
        });
    }
};
