<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'description',
        'is_active',
        'sort_order',
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

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

    /**
     * Categoría padre (para jerarquía)
     */
    public function parent()
    {
        return $this->belongsTo(MenuCategory::class, 'parent_id');
    }

    /**
     * Subcategorías (categorías hijas)
     */
    public function children()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id');
    }

    /**
     * Subcategorías activas ordenadas
     */
    public function activeChildren()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Obtener todos los descendientes (subcategorías y sub-subcategorías)
     */
    public function descendants()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id')->with('descendants');
    }

    /**
     * Obtener todos los ancestros (categorías padre hasta la raíz)
     */
    public function ancestors()
    {
        return $this->belongsTo(MenuCategory::class, 'parent_id')->with('ancestors');
    }

    /**
     * Scope para obtener solo categorías principales (sin padre)
     */
    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para obtener solo subcategorías (con padre)
     */
    public function scopeSubcategories($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Verificar si es una categoría principal
     */
    public function isMain(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Verificar si es una subcategoría
     */
    public function isSubcategory(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Verificar si tiene subcategorías
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Obtener el nivel de profundidad en la jerarquía
     */
    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * Obtener la ruta completa de la categoría (ej: "Bebidas > Licores > Whisky")
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode($separator, $path);
    }

    /**
     * Obtener todos los items de menú incluyendo subcategorías
     */
    public function allMenuItems()
    {
        return MenuItem::whereIn(
            'category_id',
            $this->descendants()->pluck('id')->push($this->id)
        );
    }
}
