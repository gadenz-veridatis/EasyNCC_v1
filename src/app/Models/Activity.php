<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'service_id',
        'activity_type_id',
        'name',
        'supplier_id',
        'start_time',
        'end_time',
        'cost',
        'cost_per_person',
        'payment_type',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cost' => 'decimal:2',
        'cost_per_person' => 'decimal:2',
    ];

    /**
     * Get the activity type
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the supplier (user with is_fornitore = true)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * Get the service this activity belongs to
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
