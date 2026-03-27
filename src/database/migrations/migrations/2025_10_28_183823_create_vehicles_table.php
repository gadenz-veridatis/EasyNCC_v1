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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('license_plate')->unique(); // targa
            $table->string('brand'); // marca
            $table->string('model'); // modello
            $table->integer('passenger_capacity'); // numero passeggeri
            $table->date('purchase_date')->nullable(); // data d'acquisto
            $table->string('ncc_license_number')->nullable(); // numero licenza NCC
            $table->string('license_city')->nullable(); // comune della licenza
            $table->boolean('allow_overlapping')->default(false); // possibile sovrapposizione orari
            $table->enum('status', ['in_service', 'maintenance', 'out_of_service'])->default('in_service'); // Stati
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
