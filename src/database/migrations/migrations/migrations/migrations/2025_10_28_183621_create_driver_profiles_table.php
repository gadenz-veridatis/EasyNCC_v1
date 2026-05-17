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
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('profile_photo')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('fiscal_code')->nullable(); // codice fiscale
            $table->string('vat_number')->nullable(); // P IVA
            $table->decimal('hourly_rate', 10, 2)->nullable(); // tariffa oraria
            $table->string('bank_name')->nullable(); // istituto bancario
            $table->string('iban')->nullable();
            $table->boolean('allow_overlapping')->default(false); // possibile sovrapposizioni su orario
            $table->string('color')->default('#3788d8'); // colore di riferimento per calendario
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
