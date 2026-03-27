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
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contactable_type'); // client_profiles, intermediary_profiles, supplier_profiles
            $table->foreignId('contactable_id');
            $table->string('name'); // referente aziendale
            $table->string('phone')->nullable(); // numero del referente
            $table->string('email')->nullable(); // email referente
            $table->timestamps();

            $table->index(['contactable_type', 'contactable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_contacts');
    }
};
