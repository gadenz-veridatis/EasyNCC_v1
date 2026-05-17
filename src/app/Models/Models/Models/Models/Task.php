<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'service_id',
        'name',
        'due_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    // Backwards compatibility accessor for single assignee
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
