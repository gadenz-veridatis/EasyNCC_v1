<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramPendingAction extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'service_id',
        'action_type',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
