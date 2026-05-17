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
    ];

    protected $hidden = [
        'client_secret',
        'refresh_token',
        'access_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
