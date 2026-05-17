<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BusinessContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'name',
        'phone',
        'email',
    ];

    // Hide these fields when serializing to array/JSON
    protected $hidden = [
        'id',
        'contactable_type',
        'contactable_id',
        'created_at',
        'updated_at'
    ];

    // Relationships
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
