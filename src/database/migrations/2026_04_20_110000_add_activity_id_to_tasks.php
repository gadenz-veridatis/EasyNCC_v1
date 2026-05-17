<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->nullable()->after('service_id');
            $table->foreign('activity_id')->references('id')->on('activities')->nullOnDelete();
        });

        // Populate existing confirmation tasks by matching activity name
        $tasks = DB::table('tasks')
            ->where('notes', 'like', '%Task di conferma automatico per l\'esperienza:%')
            ->whereNotNull('service_id')
            ->get();

        foreach ($tasks as $task) {
            if (preg_match("/Task di conferma automatico per l'esperienza: (.+)/", $task->notes, $match)) {
                $activityName = $match[1];
                $activity = DB::table('activities')
                    ->where('service_id', $task->service_id)
                    ->where('name', $activityName)
                    ->first();

                if ($activity) {
                    DB::table('tasks')->where('id', $task->id)->update(['activity_id' => $activity->id]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropColumn('activity_id');
        });
    }
};
