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
        // Migrate existing assigned_to data to task_user pivot table
        DB::statement('
            INSERT INTO task_user (task_id, user_id, created_at, updated_at)
            SELECT id, assigned_to, created_at, updated_at
            FROM tasks
            WHERE assigned_to IS NOT NULL
        ');

        // Drop the assigned_to column from tasks table (CASCADE will drop the foreign key)
        DB::statement('ALTER TABLE tasks DROP COLUMN IF EXISTS assigned_to CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the assigned_to column
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('due_date')->constrained('users')->onDelete('set null');
        });

        // Migrate first assignee back to assigned_to
        DB::statement('
            UPDATE tasks
            SET assigned_to = (
                SELECT user_id
                FROM task_user
                WHERE task_user.task_id = tasks.id
                ORDER BY task_user.id ASC
                LIMIT 1
            )
        ');

        // Clear pivot table
        DB::table('task_user')->truncate();
    }
};
