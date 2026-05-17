<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SumupConfig extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'merchant_name',
        'api_key',
        'merchant_code',
        'is_active',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
