<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Richiesta extends Model
{
    use HasFactory, HasCompany, HasUuids;

    protected $table = 'richieste';

    const STATO_NUOVA = 'nuova';
    const STATO_IN_LAVORAZIONE = 'in_lavorazione';
    const STATO_PREVENTIVATA = 'preventivata';
    const STATO_CONFERMATA = 'confermata';
    const STATO_ANNULLATA = 'annullata';
    const STATO_SOSPESA = 'sospesa';

    const FONTE_EMAIL = 'email';
    const FONTE_WEB_FORM = 'web_form';
    const FONTE_TELEFONO = 'telefono';
    const FONTE_MANUALE = 'manuale';

    const RELAZIONE_SDOPPIATA_DA = 'sdoppiata_da';
    const RELAZIONE_UNITA_CON = 'unita_con';

    protected $fillable = [
        'company_id',
        'contact_id',
        'fonte',
        'stato',
        'data_ricezione',
        'note',
        'origine_id',
        'relazione',
        'operatore_id',
        'ultimo_messaggio_inbound_at',
        'ultimo_messaggio_letto_at',
    ];

    protected $casts = [
        'data_ricezione' => 'datetime',
        'ultimo_messaggio_inbound_at' => 'datetime',
        'ultimo_messaggio_letto_at' => 'datetime',
    ];

    protected $appends = ['has_unread'];

    public function getHasUnreadAttribute(): bool
    {
        if (!$this->ultimo_messaggio_inbound_at) {
            return false;
        }
        if (!$this->ultimo_messaggio_letto_at) {
            return true;
        }
        return $this->ultimo_messaggio_inbound_at->gt($this->ultimo_messaggio_letto_at);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operatore_id');
    }

    public function origine(): BelongsTo
    {
        return $this->belongsTo(Richiesta::class, 'origine_id');
    }

    public function figlie(): HasMany
    {
        return $this->hasMany(Richiesta::class, 'origine_id');
    }

    public function righe(): HasMany
    {
        return $this->hasMany(RigaRichiesta::class, 'richiesta_id')->orderBy('ordinamento');
    }

    public function threadEmails(): HasMany
    {
        return $this->hasMany(ThreadEmail::class, 'richiesta_id')->orderBy('ricevuto_at');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'richiesta_id');
    }

    public function activeQuote()
    {
        return $this->hasOne(Quote::class, 'richiesta_id')
            ->where('is_active_version', true);
    }
}
