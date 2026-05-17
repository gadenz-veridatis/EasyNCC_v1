<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEmailToken extends Model
{
    protected $fillable = [
        'service_id',
        'company_id',
        'token',
        'action',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isValid(): bool
    {
        return !$this->used_at && $this->expires_at->isFuture();
    }
}
