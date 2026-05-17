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
        Schema::table('ztl', function (Blueprint $table) {
            $table->renameColumn('duration', 'periodicity');
            $table->dropColumn('cost');
            $table->date('expiration_date')->nullable()->after('is_active');
            $table->text('notes')->nullable()->after('expiration_date');
        });

        // Change periodicity column type from float to string
        Schema::table('ztl', function (Blueprint $table) {
            $table->string('periodicity', 255)->nullable()->change();
        });

        // Create pivot table for ZTL-Vehicle many-to-many relationship
        Schema::create('ztl_vehicle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ztl_id')->constrained('ztl')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['ztl_id', 'vehicle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ztl_vehicle');

        Schema::table('ztl', function (Blueprint $table) {
            $table->float('periodicity')->nullable()->change();
        });

        Schema::table('ztl', function (Blueprint $table) {
            $table->renameColumn('periodicity', 'duration');
            $table->decimal('cost', 10, 2)->default(0);
            $table->dropColumn(['expiration_date', 'notes']);
        });
    }
};
