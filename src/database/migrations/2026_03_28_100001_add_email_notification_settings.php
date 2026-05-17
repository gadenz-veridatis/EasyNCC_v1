<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('email_accepted_status_id')->nullable()->after('telegram_collected_status_id')
                ->constrained('service_statuses')->nullOnDelete();
            $table->foreignId('email_closed_status_id')->nullable()->after('email_accepted_status_id')
                ->constrained('service_statuses')->nullOnDelete();
            $table->string('email_notification_address')->nullable()->after('email_closed_status_id');
            $table->foreignId('email_assignment_template_id')->nullable()->after('email_notification_address')
                ->constrained('quote_email_templates')->nullOnDelete();
            $table->foreignId('email_closure_template_id')->nullable()->after('email_assignment_template_id')
                ->constrained('quote_email_templates')->nullOnDelete();
            $table->foreignId('email_gmail_account_id')->nullable()->after('email_closure_template_id')
                ->constrained('gmail_accounts')->nullOnDelete();
            $table->integer('email_token_expiry_days')->default(7)->after('email_gmail_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('email_accepted_status_id');
            $table->dropConstrainedForeignId('email_closed_status_id');
            $table->dropColumn('email_notification_address');
            $table->dropConstrainedForeignId('email_assignment_template_id');
            $table->dropConstrainedForeignId('email_closure_template_id');
            $table->dropConstrainedForeignId('email_gmail_account_id');
            $table->dropColumn('email_token_expiry_days');
        });
    }
};
