<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique email per company (only where email is not null)
            $table->unique(['company_id', 'email']);
        });

        // Add contact_id to quotes
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('client_email')
                  ->constrained()->nullOnDelete();
        });

        // Migrate existing quote data to contacts
        $this->migrateExistingData();
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::dropIfExists('contacts');
    }

    private function migrateExistingData(): void
    {
        // Get unique client_name + client_email combinations from quotes
        $uniqueClients = DB::table('quotes')
            ->select('company_id', 'client_name', 'client_email')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->groupBy('company_id', 'client_name', 'client_email')
            ->get();

        foreach ($uniqueClients as $client) {
            // Check if contact with this email already exists for this company
            $existingContact = null;
            if ($client->client_email) {
                $existingContact = DB::table('contacts')
                    ->where('company_id', $client->company_id)
                    ->where('email', $client->client_email)
                    ->first();
            }

            if ($existingContact) {
                $contactId = $existingContact->id;
            } else {
                $contactId = DB::table('contacts')->insertGetId([
                    'company_id' => $client->company_id,
                    'name' => $client->client_name,
                    'email' => $client->client_email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Link quotes to this contact
            DB::table('quotes')
                ->where('company_id', $client->company_id)
                ->where('client_name', $client->client_name)
                ->where(function ($q) use ($client) {
                    if ($client->client_email) {
                        $q->where('client_email', $client->client_email);
                    } else {
                        $q->whereNull('client_email');
                    }
                })
                ->update(['contact_id' => $contactId]);
        }
    }
};
