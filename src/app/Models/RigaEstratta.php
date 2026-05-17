<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RigaEstratta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'righe_estratte';

    public $timestamps = false;

    protected $fillable = [
        'thread_email_id',
        'ordinamento',
        'data_servizio',
        'ora_pickup',
        'tipo_servizio',
        'pickup',
        'dropoff',
        'passeggeri',
        'veicolo_preferito',
        'note',
        'confidenza',
        'riga_richiesta_id',
        'created_at',
    ];

    protected $casts = [
        'data_servizio' => 'date',
        'passeggeri' => 'integer',
        'ordinamento' => 'integer',
        'confidenza' => 'array',
    ];

    public function threadEmail(): BelongsTo
    {
        return $this->belongsTo(ThreadEmail::class);
    }

    public function rigaRichiesta(): BelongsTo
    {
        return $this->belongsTo(RigaRichiesta::class);
    }
}
