<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'is_system_permission',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system_permission' => 'boolean',
    ];

    /**
     * Relación many-to-many con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('settings')
            ->withTimestamps();
    }

    /**
     * Scope para permisos de un módulo específico
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope para permisos de una acción específica
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para permisos del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Verificar si es un permiso del sistema
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission;
    }

    /**
     * Obtener el nombre completo del permiso
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->module}.{$this->action}";
    }

    /**
     * Obtener permisos agrupados por módulo
     */
    public static function getGroupedByModule(): array
    {
        return static::orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    /**
     * Crear un permiso si no existe
     */
    public static function createIfNotExists(array $data): Permission
    {
        return static::firstOrCreate(
            ['name' => $data['name']],
            $data
        );
    }
}
