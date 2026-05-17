<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramConfig extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'bot_token',
        'bot_username',
        'webhook_url',
        'webhook_active',
    ];

    protected $casts = [
        'webhook_active' => 'boolean',
    ];

    protected $hidden = [
        'bot_token',
    ];
}
