<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('rendered_subject', 500)->nullable()->after('email_template_id');
            $table->text('rendered_body_html')->nullable()->after('rendered_subject');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['rendered_subject', 'rendered_body_html']);
        });
    }
};
