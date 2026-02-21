<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('experience_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('experience_reason', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['experience_accounting_entry_id']);
            $table->dropColumn(['experience_accounting_entry_id', 'experience_reason']);
        });
    }
};
