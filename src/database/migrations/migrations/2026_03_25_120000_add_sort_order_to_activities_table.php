<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('should_account');
        });

        // Initialize sort_order based on start_time for existing activities
        DB::statement('
            UPDATE activities
            SET sort_order = sub.row_num
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY service_id ORDER BY start_time ASC NULLS LAST, id ASC) AS row_num
                FROM activities
                WHERE deleted_at IS NULL
            ) AS sub
            WHERE activities.id = sub.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
