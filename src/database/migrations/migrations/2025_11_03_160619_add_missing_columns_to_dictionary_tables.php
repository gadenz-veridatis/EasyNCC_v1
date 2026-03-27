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
        // Add is_active column to dress_codes
        if (!Schema::hasColumn('dress_codes', 'is_active')) {
            Schema::table('dress_codes', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('is_default');
            });
        }

        // Add abbreviation and is_active columns to luggage_types (if table exists)
        if (Schema::hasTable('luggage_types')) {
            if (!Schema::hasColumn('luggage_types', 'abbreviation')) {
                Schema::table('luggage_types', function (Blueprint $table) {
                    $table->string('abbreviation', 10)->nullable()->after('name');
                });
            }
            if (!Schema::hasColumn('luggage_types', 'is_active')) {
                Schema::table('luggage_types', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('description');
                });
            }
        }

        // Add is_active column to payment_types
        if (!Schema::hasColumn('payment_types', 'is_active')) {
            Schema::table('payment_types', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });
        }

        // Add is_active column to driver_attachment_types
        if (!Schema::hasColumn('driver_attachment_types', 'is_active')) {
            Schema::table('driver_attachment_types', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('name');
            });
        }

        // Add is_active column to vehicle_attachment_types
        if (!Schema::hasColumn('vehicle_attachment_types', 'is_active')) {
            Schema::table('vehicle_attachment_types', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('name');
            });
        }

        // Add is_active column to service_statuses
        if (!Schema::hasColumn('service_statuses', 'is_active')) {
            Schema::table('service_statuses', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });
        }

        // Add is_active column to ztl
        if (!Schema::hasColumn('ztl', 'is_active')) {
            Schema::table('ztl', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('cost');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('dress_codes', 'is_active')) {
            Schema::table('dress_codes', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('luggage_types')) {
            if (Schema::hasColumn('luggage_types', 'abbreviation')) {
                Schema::table('luggage_types', function (Blueprint $table) {
                    $table->dropColumn('abbreviation');
                });
            }
            if (Schema::hasColumn('luggage_types', 'is_active')) {
                Schema::table('luggage_types', function (Blueprint $table) {
                    $table->dropColumn('is_active');
                });
            }
        }

        if (Schema::hasColumn('payment_types', 'is_active')) {
            Schema::table('payment_types', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('driver_attachment_types', 'is_active')) {
            Schema::table('driver_attachment_types', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('vehicle_attachment_types', 'is_active')) {
            Schema::table('vehicle_attachment_types', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('service_statuses', 'is_active')) {
            Schema::table('service_statuses', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('ztl', 'is_active')) {
            Schema::table('ztl', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
