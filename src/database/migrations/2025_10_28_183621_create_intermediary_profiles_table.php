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
        Schema::create('intermediary_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('fiscal_code')->nullable();
            $table->string('sdi')->nullable();
            $table->string('pec')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('operational_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('commission', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intermediary_profiles');
    }
};
