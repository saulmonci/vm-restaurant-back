<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role',
        'company_id',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con la compañía (nullable para roles globales)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación many-to-many con permisos
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('settings')
            ->withTimestamps();
    }

    /**
     * Relación con usuarios a través de user_roles
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Usuarios que tienen este rol
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['company_id', 'assigned_at', 'expires_at', 'assigned_by', 'settings', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Scope para roles activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para roles de una compañía específica
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para roles del sistema (globales)
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Verificar si el rol tiene un permiso específico
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Asignar un permiso al rol
     */
    public function givePermission(Permission $permission, array $settings = []): void
    {
        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission->id, [
                'settings' => json_encode($settings),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Revocar un permiso del rol
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Sincronizar permisos del rol
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Verificar si es un rol del sistema
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    /**
     * Verificar si es un rol de compañía
     */
    public function isCompanyRole(): bool
    {
        return !is_null($this->company_id);
    }

    /**
     * Obtener el nombre completo del rol
     */
    public function getFullNameAttribute(): string
    {
        if ($this->isCompanyRole()) {
            return "{$this->display_name} ({$this->company->name})";
        }

        return $this->display_name;
    }
}
