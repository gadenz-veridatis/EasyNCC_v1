<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->after('id');
            $table->string('client_email')->nullable()->after('client_name');
            $table->foreignId('sumup_config_id')->nullable()->after('balance_card_fees')
                ->constrained('sumup_configs')->nullOnDelete();
            $table->foreignId('gmail_account_id')->nullable()->after('sumup_config_id')
                ->constrained('gmail_accounts')->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->after('gmail_account_id')
                ->constrained('quote_email_templates')->nullOnDelete();
            $table->string('sumup_checkout_id')->nullable()->after('email_template_id');
            $table->string('sumup_checkout_url', 500)->nullable()->after('sumup_checkout_id');
            $table->string('gmail_draft_id')->nullable()->after('sumup_checkout_url');
            $table->string('gmail_thread_id')->nullable()->after('gmail_draft_id');
            $table->foreignId('service_id')->nullable()->after('gmail_thread_id')
                ->constrained('services')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('service_id');
            $table->timestamp('sent_at')->nullable()->after('approved_at');
            $table->timestamp('deposit_received_at')->nullable()->after('sent_at');

            $table->index('status');
            $table->index('sumup_checkout_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['sumup_config_id']);
            $table->dropForeign(['gmail_account_id']);
            $table->dropForeign(['email_template_id']);
            $table->dropForeign(['service_id']);
            $table->dropColumn([
                'status', 'client_email',
                'sumup_config_id', 'gmail_account_id', 'email_template_id',
                'sumup_checkout_id', 'sumup_checkout_url',
                'gmail_draft_id', 'gmail_thread_id',
                'service_id', 'approved_at', 'sent_at', 'deposit_received_at',
            ]);
        });
    }
};
