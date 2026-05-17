<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'trade_name',
        'vat_number',
        'fiscal_code',
        'sdi',
        'pec',
        'address',
        'postal_code',
        'city',
        'province',
        'country',
        'logo',
        'admin_email',
        'operational_email',
        'phone',
        'website',
        'commission',
        'is_committente',
        'is_fornitore',
    ];

    protected $casts = [
        'commission' => 'decimal:2',
        'is_committente' => 'boolean',
        'is_fornitore' => 'boolean',
    ];

    protected $appends = ['business_contacts'];

    // Accessor for business_contacts to include them in array/JSON serialization
    public function getBusinessContactsAttribute()
    {
        // If the relation is already loaded, return it
        if ($this->relationLoaded('businessContacts')) {
            return $this->getRelation('businessContacts');
        }

        // Otherwise, load and return it
        return $this->businessContacts()->get();
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessContacts(): MorphMany
    {
        return $this->morphMany(BusinessContact::class, 'contactable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
