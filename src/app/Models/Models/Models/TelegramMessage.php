<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'telegram_user_id',
        'direction',
        'message_type',
        'content',
        'telegram_message_id',
        'file_path',
        'callback_data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'telegram_message_id' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
