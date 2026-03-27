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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Riferimenti principali
            $table->string('reference_number')->nullable(); // Numero riferimento del servizio
            $table->foreignId('client_id')->nullable()->constrained('users')->onDelete('set null'); // committente
            $table->foreignId('intermediary_id')->nullable()->constrained('users')->onDelete('set null'); // intermediario
            $table->foreignId('supplier_id')->nullable()->constrained('users')->onDelete('set null'); // fornitore

            // Passeggeri
            $table->integer('passenger_count'); // numero di passeggeri

            // Veicolo
            $table->string('service_type')->nullable(); // Tipologia di servizio
            $table->string('vehicle_type')->nullable(); // Tipologia di veicolo
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict'); // veicolo assegnato
            $table->boolean('vehicle_not_replaceable')->default(false); // flag di non sostituibilità del mezzo

            // Driver
            $table->string('external_driver_name')->nullable(); // nome del driver esterno
            $table->string('external_driver_phone')->nullable(); // telefono del driver esterno
            $table->boolean('driver_not_replaceable')->default(false); // flag di non sostituibilità dell'autista

            // Dress code e bagagli
            $table->foreignId('dress_code_id')->nullable()->constrained('dress_codes')->onDelete('set null');
            $table->integer('large_luggage')->default(0); // Numero bagagli grandi
            $table->integer('medium_luggage')->default(0); // Numero bagagli medi
            $table->integer('small_luggage')->default(0); // Numero bagagli piccoli
            $table->integer('baby_seat_infant')->default(0); // Numero babyseat ovetto
            $table->integer('baby_seat_standard')->default(0); // Numero babyseat standard
            $table->integer('baby_seat_booster')->default(0); // Numero babyseat booster

            // Date e orari
            $table->dateTime('pickup_datetime'); // Data e ora pickup
            $table->text('pickup_address'); // Indirizzo pickup
            $table->string('pickup_latitude')->nullable(); // coordinate pickup
            $table->string('pickup_longitude')->nullable();
            $table->dateTime('vehicle_departure_datetime'); // Data e ora uscita mezzo

            $table->dateTime('dropoff_datetime'); // Data e ora dropoff
            $table->text('dropoff_address'); // Indirizzo dropoff
            $table->string('dropoff_latitude')->nullable(); // coordinate dropoff
            $table->string('dropoff_longitude')->nullable();
            $table->dateTime('vehicle_return_datetime'); // Data e ora rientro mezzo

            // Stato e prezzi
            $table->foreignId('status_id')->nullable()->constrained('service_statuses')->onDelete('set null'); // Stato del servizio
            $table->decimal('service_price', 10, 2)->nullable(); // Prezzo del servizio
            $table->decimal('driver_compensation', 10, 2)->nullable(); // compenso autista
            $table->decimal('intermediary_commission', 10, 2)->nullable(); // commissioni intermediario
            $table->decimal('expenses', 10, 2)->nullable(); // spese vive

            // Note
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Tabella pivot per driver assegnati (può essere più di uno)
        Schema::create('service_driver', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // driver
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_driver');
        Schema::dropIfExists('services');
    }
};
