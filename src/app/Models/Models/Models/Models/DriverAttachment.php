<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAttachment extends Model
{
    protected $fillable = [
        'user_id',
        'attachment_type',
        'expiration_date',
        'notes',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'file_size' => 'integer',
    ];

    /**
     * Get the user (driver) that owns the attachment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
