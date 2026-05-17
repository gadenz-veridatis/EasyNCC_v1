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
        Schema::create('vehicle_unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('type'); // manutenzione, noleggio, altro
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for querying unavailabilities by vehicle and date range
            $table->index(['vehicle_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_unavailabilities');
    }
};
