<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gmail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('account_label');
            $table->string('email_address');
            $table->string('client_id');
            $table->text('client_secret');
            $table->text('refresh_token');
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmail_accounts');
    }
};
