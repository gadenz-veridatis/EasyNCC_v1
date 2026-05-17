<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('website');
            $table->string('stamp')->nullable()->after('logo');
            $table->string('stamp_with_signature')->nullable()->after('stamp');
            $table->string('rea')->nullable()->after('stamp_with_signature');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo', 'stamp', 'stamp_with_signature', 'rea']);
        });
    }
};
