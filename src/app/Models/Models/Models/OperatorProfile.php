<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_contabilita',
    ];

    protected $casts = [
        'is_contabilita' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
