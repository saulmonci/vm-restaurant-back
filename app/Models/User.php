<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Company;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
}
