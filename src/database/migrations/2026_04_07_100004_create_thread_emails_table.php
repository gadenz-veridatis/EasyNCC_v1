<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thread_emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->uuid('richiesta_id')->nullable();
            $table->foreignId('mailbox_id')->constrained('gmail_accounts')->onDelete('cascade');
            $table->string('thread_id_gmail');
            $table->string('message_id_rfc')->unique();
            $table->string('in_reply_to_rfc')->nullable();
            $table->text('references_rfc')->nullable();
            $table->enum('direzione', ['inbound', 'outbound'])->default('inbound');
            $table->string('mittente');
            $table->string('destinatario');
            $table->string('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('ricevuto_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('richiesta_id')->references('id')->on('richieste')->onDelete('set null');
            $table->index('company_id');
            $table->index('richiesta_id');
            $table->index('mailbox_id');
            $table->index('thread_id_gmail');
            $table->index('in_reply_to_rfc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thread_emails');
    }
};
