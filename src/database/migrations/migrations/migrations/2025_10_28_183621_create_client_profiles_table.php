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
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name')->nullable(); // ragione sociale
            $table->string('trade_name')->nullable(); // denominazione (alias)
            $table->string('vat_number')->nullable(); // partita iva
            $table->string('fiscal_code')->nullable(); // codice fiscale
            $table->string('sdi')->nullable();
            $table->string('pec')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable(); // cap
            $table->string('city')->nullable(); // comune
            $table->string('province')->nullable();
            $table->string('country')->nullable(); // nazione
            $table->string('logo')->nullable();
            $table->string('admin_email')->nullable(); // email amministrativa
            $table->string('operational_email')->nullable(); // email operativa
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('commission', 10, 2)->nullable(); // commissione
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
