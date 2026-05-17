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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            $table->enum('role', ['super-admin', 'admin', 'operator', 'driver', 'committente', 'intermediario', 'fornitore', 'passeggero'])->default('passeggero')->after('company_id');
            $table->string('surname')->nullable()->after('name'); // cognome
            $table->string('nickname')->nullable()->after('surname');
            $table->text('address')->nullable()->after('email');
            $table->string('postal_code')->nullable()->after('address');
            $table->string('city')->nullable()->after('postal_code'); // comune
            $table->string('province')->nullable()->after('city');
            $table->string('country')->default('Italia')->nullable()->after('province');
            $table->string('phone')->nullable()->after('country');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'role',
                'surname',
                'nickname',
                'address',
                'postal_code',
                'city',
                'province',
                'country',
                'phone',
                'is_active'
            ]);
        });
    }
};
