<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Company;
use App\Models\Role;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'display_name',
        'phone',
        'address',
        'birth_date',
        'avatar_url',
        'bio',
        'is_active',
        'preferred_language',
        'timezone',
        'preferred_currency',
        'email_notifications',
        'push_notifications',
        'sms_notifications',
        'profile_public',
        'show_activity',
        'preferences',
        'last_login_at',
        'login_count',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'profile_public' => 'boolean',
        'show_activity' => 'boolean',
        'preferences' => 'array',
        'last_login_at' => 'datetime',
        'login_count' => 'integer',
        'last_activity_at' => 'datetime',
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
        ];
    }

    /**
     * Las compañías a las que pertenece el usuario.
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users');
    }

    /**
     * Los roles del usuario en las diferentes compañías.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    /**
     * Obtener roles del usuario en una compañía específica
     */
    public function rolesInCompany(int $companyId)
    {
        return $this->roles()->wherePivot('company_id', $companyId);
    }

    /**
     * Verificar si el usuario tiene un rol específico en una compañía
     */
    public function hasRole(string $roleName, int $companyId): bool
    {
        return $this->rolesInCompany($companyId)
            ->where('name', $roleName)
            ->exists();
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles especificados en una compañía
     */
    public function hasAnyRole(array $roleNames, int $companyId): bool
    {
        return $this->rolesInCompany($companyId)
            ->whereIn('name', $roleNames)
            ->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico en una compañía
     */
    public function hasPermission(string $permissionName, int $companyId): bool
    {
        $roles = $this->rolesInCompany($companyId)->with('permissions')->get();

        foreach ($roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Asignar un rol al usuario en una compañía específica
     */
    public function assignRole(string $roleName, int $companyId): bool
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return false;
        }

        // Verificar si ya tiene el rol en esa compañía
        if ($this->hasRole($roleName, $companyId)) {
            return true;
        }

        $this->roles()->attach($role->id, ['company_id' => $companyId]);

        return true;
    }

    /**
     * Remover un rol del usuario en una compañía específica
     */
    public function removeRole(string $roleName, int $companyId): bool
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return false;
        }

        $this->roles()->wherePivot('company_id', $companyId)
            ->detach($role->id);

        return true;
    }
}
