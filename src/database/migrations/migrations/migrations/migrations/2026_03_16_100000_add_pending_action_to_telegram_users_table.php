<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->string('pending_action')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn('pending_action');
        });
    }
};
