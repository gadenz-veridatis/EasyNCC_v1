<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('should_account')->default(false)->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('should_account');
        });
    }
};
