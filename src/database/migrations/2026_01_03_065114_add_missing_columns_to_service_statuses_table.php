<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_statuses', function (Blueprint $table) {
            // Add only columns that don't exist (is_active already exists)
            if (!Schema::hasColumn('service_statuses', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('service_statuses', 'color_code')) {
                $table->string('color_code', 7)->nullable()->after('description');
            }
            if (!Schema::hasColumn('service_statuses', 'notes')) {
                $table->text('notes')->nullable()->after('color_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_statuses', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('service_statuses', 'description')) {
                $columns[] = 'description';
            }
            if (Schema::hasColumn('service_statuses', 'color_code')) {
                $columns[] = 'color_code';
            }
            if (Schema::hasColumn('service_statuses', 'notes')) {
                $columns[] = 'notes';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
