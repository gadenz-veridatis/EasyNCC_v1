<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('quote_group_id')->nullable()->after('id');
            $table->integer('version')->default(1)->after('quote_group_id');
            $table->boolean('is_active_version')->default(true)->after('version');
            $table->timestamp('archived_at')->nullable()->after('updated_at');

            $table->index(['quote_group_id', 'is_active_version']);
        });

        // Initialize existing quotes: each quote is its own group
        DB::statement('UPDATE quotes SET quote_group_id = id WHERE quote_group_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['quote_group_id', 'is_active_version']);
            $table->dropColumn(['quote_group_id', 'version', 'is_active_version', 'archived_at']);
        });
    }
};
