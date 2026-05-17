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
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->bigInteger('telegram_chat_id');
            $table->bigInteger('telegram_user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'telegram_chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
