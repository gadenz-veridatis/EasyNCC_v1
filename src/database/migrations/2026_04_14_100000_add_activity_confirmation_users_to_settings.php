<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('activity_confirmation_user_ids')->nullable()->after('activity_confirmation_role');
            $table->unsignedBigInteger('activity_confirmation_default_user_id')->nullable()->after('activity_confirmation_user_ids');
            $table->foreign('activity_confirmation_default_user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Migrate existing role-based config to user-based:
        // For each company with a role set, populate user_ids with all active users of that role
        $settings = DB::table('settings')->whereNotNull('activity_confirmation_role')->get();
        foreach ($settings as $setting) {
            $userIds = DB::table('users')
                ->where('company_id', $setting->company_id)
                ->where('role', $setting->activity_confirmation_role)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            if (!empty($userIds)) {
                DB::table('settings')
                    ->where('id', $setting->id)
                    ->update(['activity_confirmation_user_ids' => json_encode($userIds)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['activity_confirmation_default_user_id']);
            $table->dropColumn(['activity_confirmation_user_ids', 'activity_confirmation_default_user_id']);
        });
    }
};
