<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
