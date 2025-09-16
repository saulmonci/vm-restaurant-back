<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    /**
     * La compañía a la que pertenece la categoría.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Los ítems de menú de la categoría.
     */
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}
