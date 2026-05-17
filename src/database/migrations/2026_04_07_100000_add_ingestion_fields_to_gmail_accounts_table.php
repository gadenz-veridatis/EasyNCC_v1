<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table) {
            $table->string('label_richieste')->nullable()->after('is_active');
            $table->string('label_richieste_id')->nullable()->after('label_richieste');
            $table->string('subject_tag')->nullable()->after('label_richieste_id');
            $table->string('history_id')->nullable()->after('subject_tag');
            $table->boolean('ingestion_attiva')->default(false)->after('history_id');
        });
    }

    public function down(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'label_richieste',
                'label_richieste_id',
                'subject_tag',
                'history_id',
                'ingestion_attiva',
            ]);
        });
    }
};
