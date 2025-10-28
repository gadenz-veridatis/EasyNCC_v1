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
        'commission',
        'notes',
    ];

    protected $casts = [
        'commission' => 'decimal:2',
    ];

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
