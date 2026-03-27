<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_mileage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->integer('mileage'); // km reading
            $table->date('entry_date'); // data lettura contachilometri
            $table->string('update_type', 20); // manual, correction, service
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['vehicle_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_mileage_entries');
    }
};
