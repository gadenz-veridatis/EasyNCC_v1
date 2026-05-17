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
            $table->json('service_cancel_status_ids')->nullable()->after('telegram_collected_status_id');
        });

        // Set defaults: find no-show and cancellato status IDs for each company
        $settings = DB::table('settings')->get();
        foreach ($settings as $setting) {
            $statusIds = DB::table('service_statuses')
                ->where('company_id', $setting->company_id)
                ->whereIn(DB::raw('LOWER(name)'), ['no-show', 'cancellato'])
                ->pluck('id')
                ->toArray();

            if (!empty($statusIds)) {
                DB::table('settings')
                    ->where('id', $setting->id)
                    ->update(['service_cancel_status_ids' => json_encode($statusIds)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('service_cancel_status_ids');
        });
    }
};
