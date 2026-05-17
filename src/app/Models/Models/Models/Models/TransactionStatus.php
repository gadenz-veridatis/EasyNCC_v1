<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionStatus extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'abbreviation',
        'color',
        'transaction_type_group',
        'is_final',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
