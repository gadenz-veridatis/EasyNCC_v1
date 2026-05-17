<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingDestination extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'destination',
        'service_type',
        'duration_hours',
        'mileage_km',
        'toll_cost',
        'experience_a',
        'experience_b',
        'experience_c',
        'experience_d',
        'sort_order',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'mileage_km' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'experience_a' => 'decimal:2',
        'experience_b' => 'decimal:2',
        'experience_c' => 'decimal:2',
        'experience_d' => 'decimal:2',
        'sort_order' => 'integer',
    ];
}
