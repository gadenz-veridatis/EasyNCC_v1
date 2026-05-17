<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramNotification extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'telegram_user_id',
        'telegram_message_id',
        'type',
        'title',
        'body',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function telegramMessage(): BelongsTo
    {
        return $this->belongsTo(TelegramMessage::class);
    }
}
