<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Role;

class Company extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'country',
        'owner_id',
        'description',
        'phone',
        'email',
        'is_active',
        'open_hour',
        'close_hour',
        'delivery_enabled',
        'pickup_enabled',
        'delivery_radius',
        'min_order_amount',
        'delivery_fee',
        'estimated_delivery_time',
        'payment_methods',
        'has_happy_hour',
        'settings', // JSON column for custom settings
        'logo_url',
        'website',
        'timezone',
        'currency',
        'language',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'delivery_enabled' => 'boolean',
        'pickup_enabled' => 'boolean',
        'has_happy_hour' => 'boolean',
        'delivery_radius' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'payment_methods' => 'array',
        'open_hour' => 'integer',
        'close_hour' => 'integer',
        'estimated_delivery_time' => 'integer',
        'settings' => 'array', // Cast JSON to array
    ];

    /**
     * Los usuarios que pertenecen a la compañía.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users');
    }

    /**
     * El propietario de la compañía.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Las categorías de menú de la compañía.
     */
    public function categories()
    {
        return $this->hasMany(MenuCategory::class);
    }

    /**
     * Alias para las categorías de menú.
     */
    public function menuCategories()
    {
        return $this->hasMany(MenuCategory::class);
    }

    /**
     * Los ítems de menú de la compañía.
     */
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Los roles disponibles en esta compañía.
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Los usuarios que tienen roles en esta compañía.
     */
    public function usersWithRoles()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('role_id')
            ->withTimestamps();
    }
}
