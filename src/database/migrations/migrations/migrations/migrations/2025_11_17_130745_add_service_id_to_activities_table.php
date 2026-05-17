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
            $table->foreignId('service_id')->nullable()->after('company_id')->constrained('services')->onDelete('cascade');
            $table->decimal('cost', 10, 2)->nullable()->default(null)->change();
            $table->decimal('cost_per_person', 10, 2)->nullable()->default(null)->change();
        });

        // For PostgreSQL, we need to use raw SQL to modify the enum column
        DB::statement("ALTER TABLE activities ALTER COLUMN payment_type DROP DEFAULT");
        DB::statement("ALTER TABLE activities ALTER COLUMN payment_type DROP NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
            $table->decimal('cost', 10, 2)->default(0)->change();
            $table->decimal('cost_per_person', 10, 2)->default(0)->change();
        });

        // Restore NOT NULL and default for payment_type
        DB::statement("ALTER TABLE activities ALTER COLUMN payment_type SET DEFAULT 'NESSUNO'");
        DB::statement("ALTER TABLE activities ALTER COLUMN payment_type SET NOT NULL");
    }
};
