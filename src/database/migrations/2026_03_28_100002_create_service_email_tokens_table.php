<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_email_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->string('action', 20); // 'accept' or 'close'
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['token', 'action']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_email_tokens');
    }
};
