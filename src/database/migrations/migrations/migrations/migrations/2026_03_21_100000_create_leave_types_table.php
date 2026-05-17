<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default values for all companies
        $companies = DB::table('companies')->pluck('id');
        $defaults = ['Ferie', 'Malattia', 'Permesso', 'Altro'];
        $now = now();

        foreach ($companies as $companyId) {
            foreach ($defaults as $name) {
                DB::table('leave_types')->insert([
                    'company_id' => $companyId,
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
