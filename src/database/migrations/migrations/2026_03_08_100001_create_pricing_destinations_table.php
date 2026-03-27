<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Display name (e.g. "Chianti Classico - TOUR HD")');
            $table->string('destination')->comment('Destination name (e.g. "Chianti Classico")');
            $table->string('service_type')->comment('TOUR HD, TOUR FD, TOUR FD+, TRF');
            $table->decimal('duration_hours', 8, 2)->default(0);
            $table->decimal('mileage_km', 10, 2)->default(0);
            $table->decimal('toll_cost', 10, 2)->default(0);
            $table->decimal('experience_a', 10, 2)->default(0);
            $table->decimal('experience_b', 10, 2)->default(0);
            $table->decimal('experience_c', 10, 2)->default(0);
            $table->decimal('experience_d', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_destinations');
    }
};
