<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('default_sumup_config_id')->nullable()
                ->constrained('sumup_configs')->nullOnDelete();
            $table->foreignId('default_gmail_account_id')->nullable()
                ->constrained('gmail_accounts')->nullOnDelete();
            $table->foreignId('default_email_template_id')->nullable()
                ->constrained('quote_email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['default_sumup_config_id']);
            $table->dropForeign(['default_gmail_account_id']);
            $table->dropForeign(['default_email_template_id']);
            $table->dropColumn([
                'default_sumup_config_id',
                'default_gmail_account_id',
                'default_email_template_id',
            ]);
        });
    }
};
