<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_payment_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index('company_id');
        });

        // Seed defaults for all existing companies
        $companies = DB::table('companies')->pluck('id');
        $now = now();

        $defaults = [
            ['code' => 'INCLUSO',  'name' => 'Incluso',  'color' => '#0ab39c', 'sort_order' => 1],
            ['code' => 'CLIENTE',  'name' => 'Cliente',  'color' => '#4b38b3', 'sort_order' => 2],
            ['code' => 'AGENZIA',  'name' => 'Agenzia',  'color' => '#f7b84b', 'sort_order' => 3],
            ['code' => 'NESSUNO',  'name' => 'Nessuno',  'color' => '#6c757d', 'sort_order' => 4],
        ];

        foreach ($companies as $companyId) {
            foreach ($defaults as $default) {
                DB::table('activity_payment_types')->insert(array_merge($default, [
                    'company_id' => $companyId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_payment_types');
    }
};
