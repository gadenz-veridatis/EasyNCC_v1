<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GmailAccount extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'account_label',
        'email_address',
        'client_id',
        'client_secret',
        'refresh_token',
        'access_token',
        'token_expires_at',
        'is_active',
        'label_richieste',
        'label_richieste_id',
        'subject_tag',
        'history_id',
        'ingestion_attiva',
    ];

    protected $hidden = [
        'client_secret',
        'refresh_token',
        'access_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'ingestion_attiva' => 'boolean',
    ];

    public function threadEmails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ThreadEmail::class, 'mailbox_id');
    }
}
