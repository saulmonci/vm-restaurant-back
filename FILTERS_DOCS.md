# Documentación de Filtros Avanzados

## Sistema de Filtros con Query Builder

Ahora puedes implementar filtros complejos de manera muy fácil usando el query builder de Laravel.

### Cómo Funciona

1. **CRUDController** detecta automáticamente si hay filtros
2. **buildFilterCallback()** crea una función que se pasa al Repository
3. **Repository** aplica la función al query builder
4. **applyFilters()** en cada Controller define filtros específicos
5. **applyCommonFilters()** aplica filtros generales (búsqueda, ordenamiento, fechas)

### Filtros Automáticos Disponibles

Estos filtros funcionan en **todos** los controllers que extienden CRUDController:

| Parámetro        | Tipo   | Descripción                                           |
| ---------------- | ------ | ----------------------------------------------------- |
| `search`         | string | Busca en campos definidos en `$searchable` del modelo |
| `sort_by`        | string | Campo por el que ordenar                              |
| `sort_direction` | string | `asc` o `desc` (default: `asc`)                       |
| `created_after`  | date   | Elementos creados después de esta fecha               |
| `created_before` | date   | Elementos creados antes de esta fecha                 |
| `ids`            | string | Lista de IDs separados por coma                       |

### Filtros Específicos de MenuItems

| Parámetro                 | Tipo    | Descripción                                     |
| ------------------------- | ------- | ----------------------------------------------- |
| `category_id`             | integer | ID de categoría específica                      |
| `company_id`              | integer | ID de empresa específica                        |
| `available`               | boolean | Solo items disponibles                          |
| `min_price` / `max_price` | float   | Rango de precios                                |
| `vegetarian`              | boolean | Solo items vegetarianos                         |
| `vegan`                   | boolean | Solo items veganos                              |
| `gluten_free`             | boolean | Solo items sin gluten                           |
| `max_spice_level`         | integer | Nivel máximo de picante                         |
| `max_calories`            | integer | Calorías máximas                                |
| `max_prep_time`           | integer | Tiempo máximo de preparación                    |
| `category_name`           | string  | Buscar por nombre de categoría                  |
| `company_name`            | string  | Buscar por nombre de empresa                    |
| `only_active_categories`  | boolean | Solo de categorías activas                      |
| `only_open_companies`     | boolean | Solo de empresas abiertas                       |
| `category_ids`            | string  | Lista de IDs de categorías (separados por coma) |
| `has_ingredient`          | string  | Debe contener este ingrediente                  |
| `exclude_allergen`        | string  | No debe contener este alérgeno                  |

## Ejemplos de URLs Completas

### 1. Filtros Básicos

```
GET /menu-items?paginate=true&perPage=10&available=true&vegetarian=true&min_price=15&max_price=25
```

### 2. Búsqueda y Ordenamiento

```
GET /menu-items?search=pizza&sort_by=price&sort_direction=asc&paginate=true
```

### 3. Filtros Complejos con Relaciones

```
GET /menu-items?company_name=Restaurante&category_name=Hamburguesas&only_active_categories=true&max_prep_time=20
```

### 4. Filtros Dietarios y Nutricionales

```
GET /menu-items?vegan=true&gluten_free=true&max_calories=500&max_spice_level=2&exclude_allergen=nuts
```

### 5. Múltiples Categorías

```
GET /menu-items?category_ids=1,2,3&available=true&sort_by=name
```

### 6. Filtros por Fecha y IDs

```
GET /menu-items?created_after=2025-01-01&ids=1,5,10,15&sort_by=created_at&sort_direction=desc
```

## Implementación en Nuevos Controllers

### Paso 1: Extender CRUDController

```php
class MyController extends CRUDController
{
    protected $resourceClass = MyResource::class;

    public function __construct(MyRepository $repository)
    {
        parent::__construct($repository);
    }
}
```

### Paso 2: Definir Filtros

```php
protected function getFilterParameters(Request $request): array
{
    $filters = [];

    if ($request->has('status')) {
        $filters['status'] = $request->string('status');
    }

    if ($request->has('active')) {
        $filters['is_active'] = $request->boolean('active');
    }

    return $filters;
}
```

### Paso 3: Aplicar Filtros al Query

```php
protected function applyFilters($query, array $filters)
{
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (isset($filters['is_active'])) {
        $query->where('is_active', $filters['is_active']);
    }

    // Filtros con relaciones
    if (isset($filters['user_name'])) {
        $query->whereHas('user', function($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['user_name'] . '%');
        });
    }

    // Filtros complejos
    if (isset($filters['date_range'])) {
        $range = explode(',', $filters['date_range']);
        if (count($range) === 2) {
            $query->whereBetween('created_at', $range);
        }
    }

    return $query;
}
```

### Paso 4: (Opcional) Agregar Búsqueda al Modelo

```php
use App\Traits\Searchable;

class MyModel extends Model
{
    use Searchable;

    protected $searchable = [
        'name',
        'description',
        'code',
    ];
}
```

## Tipos de Filtros Avanzados

### 1. Filtros con OR Logic

```php
if (isset($filters['search_any'])) {
    $term = $filters['search_any'];
    $query->where(function($q) use ($term) {
        $q->where('name', 'like', "%$term%")
          ->orWhere('description', 'like', "%$term%")
          ->orWhere('code', 'like', "%$term%");
    });
}
```

### 2. Filtros con Relaciones Anidadas

```php
if (isset($filters['company_city'])) {
    $query->whereHas('category.company', function($q) use ($filters) {
        $q->where('city', $filters['company_city']);
    });
}
```

### 3. Filtros con Conteos

```php
if (isset($filters['min_items'])) {
    $query->withCount('menuItems')
          ->having('menu_items_count', '>=', $filters['min_items']);
}
```

### 4. Filtros con JSON

```php
if (isset($filters['has_feature'])) {
    $query->whereJsonContains('features', $filters['has_feature']);
}

if (isset($filters['setting_value'])) {
    $query->where('settings->notifications->email', $filters['setting_value']);
}
```

### 5. Filtros con Rangos

```php
if (isset($filters['price_range'])) {
    [$min, $max] = explode('-', $filters['price_range']);
    $query->whereBetween('price', [(float)$min, (float)$max]);
}
```

## Validación y Seguridad

### Validar Campos de Ordenamiento

```php
protected function applyFilters($query, array $filters)
{
    $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];

    if (isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortFields)) {
        $direction = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($filters['sort_by'], $direction);
    }

    return $query;
}
```

### Limitar Resultados

```php
// En getFilterParameters()
if ($request->has('limit')) {
    $filters['limit'] = min($request->integer('limit'), 100); // Máximo 100
}

// En applyFilters()
if (isset($filters['limit'])) {
    $query->limit($filters['limit']);
}
```

Esta implementación te da máxima flexibilidad para crear filtros complejos de manera muy sencilla! 🚀
