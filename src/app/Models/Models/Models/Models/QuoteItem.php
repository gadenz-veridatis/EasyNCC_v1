<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'pricing_destination_id',
        'destination_name',
        'service_type',
        'mileage',
        'extra_km',
        'duration_hours',
        'extension_hours',
        'extra_travel_hours',
        'toll_cost',
        'pax_count',
        'experience_per_pax',
        'taxable_price',
        'sort_order',
    ];

    protected $casts = [
        'mileage' => 'decimal:2',
        'extra_km' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'extension_hours' => 'decimal:2',
        'extra_travel_hours' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'pax_count' => 'integer',
        'experience_per_pax' => 'decimal:2',
        'taxable_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function pricingDestination(): BelongsTo
    {
        return $this->belongsTo(PricingDestination::class);
    }
}
