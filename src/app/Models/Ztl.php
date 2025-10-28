<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ztl extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $table = 'ztl';

    protected $fillable = [
        'company_id',
        'city',
        'duration',
        'cost',
    ];

    protected $casts = [
        'duration' => 'integer',
        'cost' => 'decimal:2',
    ];
}
