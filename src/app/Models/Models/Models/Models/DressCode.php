<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DressCode extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'color_code',
        'notes',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
