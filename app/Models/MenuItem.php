<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Searchable;

class MenuItem extends Model
{
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'description',
        'price',
        'is_available',
        'image_url',
        'ingredients',
        'allergens',
        'nutritional_info',
        'preparation_time',
        'spice_level',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'calories',
        'sort_order',
    ];

    /**
     * The searchable fields for general search
     *
     * @var array<int, string>
     */
    protected $searchable = [
        'name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_vegetarian' => 'boolean',
        'is_vegan' => 'boolean',
        'is_gluten_free' => 'boolean',
        'ingredients' => 'array',
        'allergens' => 'array',
        'nutritional_info' => 'array',
        'preparation_time' => 'integer',
        'spice_level' => 'integer',
        'calories' => 'integer',
        'sort_order' => 'integer',
    ];

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
