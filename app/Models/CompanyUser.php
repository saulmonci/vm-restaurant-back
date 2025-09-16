<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'role',
        'is_main',
        'permissions',
        'hired_at',
        'salary',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'hired_at' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * El usuario de la relación.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La compañía de la relación.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
