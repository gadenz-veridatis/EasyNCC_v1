<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('confirmation_enabled')->default(false)->after('should_account');
            $table->unsignedBigInteger('confirmation_assignee_id')->nullable()->after('confirmation_enabled');
            $table->foreign('confirmation_assignee_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['confirmation_assignee_id']);
            $table->dropColumn(['confirmation_enabled', 'confirmation_assignee_id']);
        });
    }
};
