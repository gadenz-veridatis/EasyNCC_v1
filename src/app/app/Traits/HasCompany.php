<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCompany
{
    /**
     * Boot the HasCompany trait.
     */
    protected static function bootHasCompany(): void
    {
        // Automatically scope queries by company_id if user is not super-admin
        static::addGlobalScope('company', function (Builder $builder) {
            $user = auth()->user();

            if ($user && $user->role !== 'super-admin') {
                $builder->where('company_id', $user->company_id);
            }
        });

        // Automatically set company_id when creating
        static::creating(function ($model) {
            $user = auth()->user();

            if (!$model->company_id && $user && $user->role !== 'super-admin') {
                $model->company_id = $user->company_id;
            }
        });
    }

    /**
     * Get the company that owns the model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Scope a query to only include models for a specific company.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
