<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('gmail_label_richieste')->nullable()->after('pricing_toll');
            $table->string('gmail_subject_tag')->nullable()->after('gmail_label_richieste');
            $table->integer('gmail_polling_interval')->default(60)->after('gmail_subject_tag');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'gmail_label_richieste',
                'gmail_subject_tag',
                'gmail_polling_interval',
            ]);
        });
    }
};
