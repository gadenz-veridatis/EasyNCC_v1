<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'abbreviation',
        'notes',
    ];

    protected $appends = ['description'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get description attribute (alias for notes)
     */
    public function getDescriptionAttribute()
    {
        return $this->notes;
    }

    /**
     * Set description attribute (maps to notes)
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['notes'] = $value;
    }
}
