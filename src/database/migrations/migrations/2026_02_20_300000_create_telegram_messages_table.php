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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->onDelete('cascade');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('message_type', ['text', 'document', 'callback']);
            $table->text('content')->nullable();
            $table->bigInteger('telegram_message_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('callback_data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'telegram_user_id']);
            $table->index(['company_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
