<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'telegram_chat_id',
        'telegram_user_id',
        'first_name',
        'last_name',
        'username',
        'user_id',
        'is_active',
        'pending_action',
    ];

    protected $casts = [
        'telegram_chat_id' => 'integer',
        'telegram_user_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TelegramNotification::class);
    }
}
