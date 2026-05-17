<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'notes',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
