<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_closed_ok_status_id')->nullable()->after('telegram_accepted_status_id');
            $table->unsignedBigInteger('telegram_closed_ko_status_id')->nullable()->after('telegram_closed_ok_status_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['telegram_closed_ok_status_id', 'telegram_closed_ko_status_id']);
        });
    }
};
