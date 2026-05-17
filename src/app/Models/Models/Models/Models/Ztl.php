<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ztl extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $table = 'ztl';

    protected $fillable = [
        'company_id',
        'city',
        'periodicity',
        'expiration_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'ztl_vehicle')->withTimestamps();
    }
}
