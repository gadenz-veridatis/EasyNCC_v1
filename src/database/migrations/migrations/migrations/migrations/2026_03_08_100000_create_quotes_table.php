<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Client & service info
            $table->string('client_name');
            $table->date('service_date')->nullable();
            $table->string('reference_url')->nullable();
            $table->text('notes')->nullable();

            // Service details (inputs)
            $table->string('destination_name')->nullable();
            $table->string('service_type')->nullable();
            $table->decimal('mileage', 10, 2)->default(0);
            $table->decimal('extra_km', 10, 2)->default(0);
            $table->decimal('duration_hours', 8, 2)->default(0);
            $table->decimal('extension_hours', 8, 2)->default(0);
            $table->decimal('extra_travel_hours', 8, 2)->default(0);
            $table->decimal('toll_cost', 10, 2)->default(0);
            $table->integer('pax_count')->default(0);
            $table->decimal('experience_per_pax', 10, 2)->default(0);

            // Pricing policies (inputs)
            $table->string('seasonality')->default('average'); // low, average, high
            $table->string('vehicle_fill')->default('car'); // car, van, full
            $table->decimal('vat_percentage', 5, 2)->default(10.00);
            $table->decimal('card_fees_percentage', 5, 2)->default(5.00);
            $table->decimal('surcharge_percentage', 5, 2)->default(0);
            $table->decimal('travel_costs', 10, 2)->default(0);

            // Calculated results
            $table->decimal('taxable_transport', 10, 2)->default(0);
            $table->decimal('taxable_experience', 10, 2)->default(0);
            $table->decimal('surcharge_amount', 10, 2)->default(0);
            $table->decimal('taxable_price', 10, 2)->default(0);
            $table->decimal('taxable_price_rounded', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('cc_fees_amount', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);
            $table->decimal('final_price_rounded', 10, 2)->default(0);

            // Discount & deposit
            $table->decimal('client_price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('deposit_percentage', 5, 2)->default(30.00);
            $table->decimal('deposit_taxable', 10, 2)->default(0);
            $table->decimal('deposit_full', 10, 2)->default(0);
            $table->decimal('balance_taxable', 10, 2)->default(0);
            $table->decimal('balance_bank', 10, 2)->default(0);
            $table->decimal('balance_cc', 10, 2)->default(0);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
