<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('aviationstack_api_key')->nullable()->after('email_token_expiry_days');
            $table->boolean('flight_tracking_enabled')->default(false)->after('aviationstack_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['aviationstack_api_key', 'flight_tracking_enabled']);
        });
    }
};
