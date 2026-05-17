<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->string('type')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->string('type')->nullable(false)->default(null)->change();
        });
    }
};
