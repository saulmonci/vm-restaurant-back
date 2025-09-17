<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
        'company_id',
        'assigned_at',
        'expires_at',
        'assigned_by',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el rol
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relación con la compañía
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con el usuario que asignó el rol
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope para roles activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para roles no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para roles de una compañía específica
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para un usuario específico
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Verificar si el rol está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar si el rol está activo y no expirado
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Extender la expiración del rol
     */
    public function extendExpiration(Carbon $newExpiration): bool
    {
        return $this->update(['expires_at' => $newExpiration]);
    }

    /**
     * Activar/desactivar el rol
     */
    public function setActive(bool $active): bool
    {
        return $this->update(['is_active' => $active]);
    }

    /**
     * Boot method para eventos del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-asignar fecha de asignación
        static::creating(function ($userRole) {
            if (!$userRole->assigned_at) {
                $userRole->assigned_at = now();
            }
        });
    }
}
