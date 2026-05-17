<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteStateTransition extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'quote_id',
        'company_id',
        'from_state',
        'to_state',
        'transitioned_by',
        'transition_source',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }
}
