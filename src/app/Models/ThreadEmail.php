<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadEmail extends Model
{
    use HasFactory, HasCompany, HasUuids;

    protected $table = 'thread_emails';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'richiesta_id',
        'mailbox_id',
        'thread_id_gmail',
        'message_id_rfc',
        'in_reply_to_rfc',
        'references_rfc',
        'direzione',
        'mittente',
        'destinatario',
        'subject',
        'body_text',
        'body_html',
        'ricevuto_at',
        'created_at',
    ];

    protected $casts = [
        'ricevuto_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function richiesta(): BelongsTo
    {
        return $this->belongsTo(Richiesta::class);
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class, 'mailbox_id');
    }

    public function righeEstratte(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RigaEstratta::class)->orderBy('ordinamento');
    }
}
