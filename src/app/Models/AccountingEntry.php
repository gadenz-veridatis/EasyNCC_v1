<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'abbreviation',
        'type',
        'notes',
        'description',
    ];

    protected $appends = ['description'];

    /**
     * Get the company that owns the accounting entry.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the description attribute (alias for notes).
     */
    public function getDescriptionAttribute()
    {
        return $this->notes;
    }

    /**
     * Set the description attribute (alias for notes).
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['notes'] = $value;
    }
}
