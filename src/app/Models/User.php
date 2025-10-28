<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable // implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'role',
        'name',
        'surname',
        'nickname',
        'email',
        'password',
        'address',
        'postal_code',
        'city',
        'province',
        'country',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function intermediaryProfile()
    {
        return $this->hasOne(IntermediaryProfile::class);
    }

    public function supplierProfile()
    {
        return $this->hasOne(SupplierProfile::class);
    }

    // Services where user is client
    public function clientServices()
    {
        return $this->hasMany(Service::class, 'client_id');
    }

    // Services where user is intermediary
    public function intermediaryServices()
    {
        return $this->hasMany(Service::class, 'intermediary_id');
    }

    // Services where user is supplier
    public function supplierServices()
    {
        return $this->hasMany(Service::class, 'supplier_id');
    }

    // Services where user is driver (many-to-many)
    public function driverServices()
    {
        return $this->belongsToMany(Service::class, 'service_driver');
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isClient(): bool
    {
        return $this->role === 'committente';
    }

    public function isIntermediary(): bool
    {
        return $this->role === 'intermediario';
    }

    public function isSupplier(): bool
    {
        return $this->role === 'fornitore';
    }

    public function isPassenger(): bool
    {
        return $this->role === 'passeggero';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
