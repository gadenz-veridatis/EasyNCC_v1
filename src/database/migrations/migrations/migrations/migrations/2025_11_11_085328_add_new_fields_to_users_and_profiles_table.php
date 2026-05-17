<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->boolean('is_intermediario')->default(false)->after('is_active');
            $table->decimal('percentuale_commissione', 5, 2)->nullable()->after('is_intermediario');
        });

        // Generate username for existing users (email without domain + id to ensure uniqueness)
        DB::statement("UPDATE users SET username = CONCAT(SPLIT_PART(email, '@', 1), '_', id) WHERE username IS NULL");

        // Add new fields to client_profiles table
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->boolean('is_committente')->default(false)->after('commission');
            $table->boolean('is_fornitore')->default(false)->after('is_committente');
        });

        // Create operator_profiles table for operator-specific fields
        Schema::create('operator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_contabilita')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove fields from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'is_intermediario', 'percentuale_commissione']);
        });

        // Remove fields from client_profiles table
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_committente', 'is_fornitore']);
        });

        // Drop operator_profiles table
        Schema::dropIfExists('operator_profiles');
    }
};
