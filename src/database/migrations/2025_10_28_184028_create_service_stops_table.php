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
        Schema::create('service_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('cost_per_person', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('buyer', ['client', 'passenger', 'intermediary', 'included']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_stops');
    }
};
