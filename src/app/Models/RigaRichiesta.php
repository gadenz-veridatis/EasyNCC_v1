<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RigaRichiesta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'righe_richiesta';

    public $timestamps = false;

    protected $fillable = [
        'richiesta_id',
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
        'riga_estratta_origine_id',
        'modificata_manualmente',
        'created_at',
    ];

    protected $casts = [
        'data_servizio' => 'date',
        'passeggeri' => 'integer',
        'ordinamento' => 'integer',
        'confidenza' => 'array',
        'modificata_manualmente' => 'boolean',
    ];

    public function richiesta(): BelongsTo
    {
        return $this->belongsTo(Richiesta::class);
    }

    public function rigaEstrattaOrigine(): BelongsTo
    {
        return $this->belongsTo(RigaEstratta::class, 'riga_estratta_origine_id');
    }
}
