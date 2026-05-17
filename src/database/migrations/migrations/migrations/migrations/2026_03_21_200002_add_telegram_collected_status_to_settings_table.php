<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_collected_status_id')->nullable()->after('telegram_closed_ko_status_id');

            $table->foreign('telegram_collected_status_id')
                ->references('id')
                ->on('service_statuses')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['telegram_collected_status_id']);
            $table->dropColumn('telegram_collected_status_id');
        });
    }
};
