<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /**
     * Los usuarios que pertenecen a la compañía.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users');
    }

    /**
     * Las categorías de menú de la compañía.
     */
    public function categories()
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
}
