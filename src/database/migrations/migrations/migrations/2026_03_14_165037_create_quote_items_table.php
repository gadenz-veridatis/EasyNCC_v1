<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_destination_id')->nullable()->constrained('pricing_destinations')->nullOnDelete();
            $table->string('destination_name')->nullable();
            $table->string('service_type', 50)->nullable();
            $table->decimal('mileage', 10, 2)->default(0);
            $table->decimal('extra_km', 10, 2)->default(0);
            $table->decimal('duration_hours', 8, 2)->default(0);
            $table->decimal('extension_hours', 8, 2)->default(0);
            $table->decimal('extra_travel_hours', 8, 2)->default(0);
            $table->decimal('toll_cost', 10, 2)->default(0);
            $table->integer('pax_count')->default(0);
            $table->decimal('experience_per_pax', 10, 2)->default(0);
            $table->decimal('taxable_price', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrate existing quote data into quote_items
        $quotes = DB::table('quotes')
            ->whereNull('deleted_at')
            ->orWhere(function ($query) {
                $query->whereNotNull('deleted_at');
            })
            ->select([
                'id', 'destination_name', 'service_type', 'mileage', 'extra_km',
                'duration_hours', 'extension_hours', 'extra_travel_hours',
                'toll_cost', 'pax_count', 'experience_per_pax', 'taxable_price_rounded',
            ])
            ->get();

        $now = now();
        foreach ($quotes as $quote) {
            // Only create item if there's meaningful data
            if ($quote->destination_name || $quote->mileage > 0 || $quote->taxable_price_rounded > 0) {
                DB::table('quote_items')->insert([
                    'quote_id' => $quote->id,
                    'destination_name' => $quote->destination_name,
                    'service_type' => $quote->service_type,
                    'mileage' => $quote->mileage ?? 0,
                    'extra_km' => $quote->extra_km ?? 0,
                    'duration_hours' => $quote->duration_hours ?? 0,
                    'extension_hours' => $quote->extension_hours ?? 0,
                    'extra_travel_hours' => $quote->extra_travel_hours ?? 0,
                    'toll_cost' => $quote->toll_cost ?? 0,
                    'pax_count' => $quote->pax_count ?? 0,
                    'experience_per_pax' => $quote->experience_per_pax ?? 0,
                    'taxable_price' => $quote->taxable_price_rounded ?? 0,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
