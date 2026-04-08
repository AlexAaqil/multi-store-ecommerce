<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Users\Address;
use App\Models\Shops\Shop;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\UserRoles;
use App\Enums\UserStatuses;
use App\Concerns\HasUuid;

// #[Fillable(['name', 'email', 'password'])]
// #[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;
    use HasUuid;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $appends = [
        'role_label',
        'status_label',
        'is_active',
        // 'branch'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRoles::class,
            'status' => UserStatuses::class,
        ];
    }

    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function activeShop(): HasOne
    {
        return $this->hasOne(Shop::class, 'owner_id')->where('is_active', true);
    }

    public function hasRole(string $role_name): bool
    {
        // Convert string role name to enum value
        foreach (UserRoles::cases() as $role) {
            if (strtolower($role->name) === strtolower($role_name)) {
                return $this->role->value === $role->value;
            }
        }
        return false;
    }
    
    public function hasAnyRole(array $role_names): bool
    {
        foreach ($role_names as $role_name) {
            if ($this->hasRole($role_name)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRoles::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRoles::ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->role === UserRoles::SELLER;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRoles::CUSTOMER;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatuses::ACTIVE;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role->label();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function scopeOrderByRolePriority($query)
    {
        return $query->orderByRaw(
            "CASE
                WHEN role = ? THEN 1
                WHEN role = ? THEN 2
                WHEN role = ? THEN 3
                WHEN role = ? THEN 4
                ELSE 5
            END ASC",
            [
                UserRoles::SUPER_ADMIN->value,
                UserRoles::ADMIN->value,
                UserRoles::SELLER->value,
                UserRoles::CUSTOMER->value,
            ]
        )->orderBy('name');
    }

    public function scopeFilterByRole($query, $role)
    {
        // If role is empty string or null, don't filter
        if ($role === null || $role === '' || $role === 'null') {
            return $query;
        }

        // Handle numeric values
        if (is_numeric($role)) {
            return $query->where('role', (int) $role);
        }
        
        // Handle string labels (for direct label filtering)
        $roleEnum = UserRoles::tryFromLabel($role);
        if ($roleEnum) {
            return $query->where('role', $roleEnum->value);
        }
        
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;
        
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
