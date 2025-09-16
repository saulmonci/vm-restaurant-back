<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    /**
     * La compañía a la que pertenece el ítem.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * La categoría a la que pertenece el ítem.
     */
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }
}
