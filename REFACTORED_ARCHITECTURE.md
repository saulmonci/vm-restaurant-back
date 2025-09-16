# Documentación de la Arquitectura de Filtros - Refactorizada

## Separación de Responsabilidades

Hemos refactorizado la arquitectura para mantener una clara separación de responsabilidades:

### Controller (Presentación)

-   Solo maneja la presentación de datos
-   Define qué relaciones cargar (eager loading)
-   Se enfoca en el formato de respuesta HTTP

### Repository (Lógica de Datos)

-   Maneja toda la lógica de filtros y consultas
-   Procesa el Request directamente
-   Aplica filtros específicos y comunes

---

## Cómo Funciona

### 1. Controller Simplificado

```php
class MenuItemController extends CRUDController
{
    protected $resourceClass = MenuItemResource::class;

    public function __construct(MenuItemRepository $repository)
    {
        parent::__construct($repository);
    }

    // Solo define qué relaciones cargar
    protected function getIndexEagerLoadRelations(): array
    {
        return ['category.company'];
    }
}
```

### 2. Repository con Filtros

```php
class MenuItemRepository extends BaseRepository
{
    // Implementa filtros específicos
    protected function applySpecificFilters($query, Request $request)
    {
        if ($request->has('available')) {
            $query->where('available', $request->boolean('available'));
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->numeric('min_price'));
        }

        // ... más filtros específicos
    }
}
```

### 3. BaseRepository con Filtros Comunes

```php
abstract class BaseRepository implements BaseRepositoryInterface
{
    // Filtros que todos los repositorios pueden usar
    protected function applyCommonFilters($query, Request $request)
    {
        // Búsqueda general
        if ($request->has('search')) {
            // Lógica de búsqueda...
        }

        // Ordenamiento
        if ($request->has('sort_by')) {
            // Lógica de ordenamiento...
        }

        // Filtros por fecha
        if ($request->has('created_after')) {
            // Filtros temporales...
        }
    }
}
```

---

## API Actualizada

### Interface BaseRepositoryInterface

```php
interface BaseRepositoryInterface
{
    public function allWithFilters(Request $request, array $relations = []);
    public function paginateWithFilters(Request $request, int $perPage = 15, array $relations = []);
    // ... otros métodos
}
```

### Uso en Controller

```php
public function index(Request $request)
{
    $relations = $this->getIndexEagerLoadRelations();

    if ($request->boolean('paginate', false)) {
        $items = $this->repository->paginateWithFilters($request, $perPage, $relations);
    } else {
        $items = $this->repository->allWithFilters($request, $relations);
    }

    return $this->resourceClass::collection($items);
}
```

---

## Ventajas de Esta Arquitectura

### 1. **Separación Clara de Responsabilidades**

-   Controller: Solo presentación
-   Repository: Solo lógica de datos
-   Cada capa tiene una responsabilidad específica

### 2. **Reutilización de Filtros Comunes**

-   Búsqueda, ordenamiento, filtros temporales se aplican automáticamente
-   No hay duplicación de código entre controllers

### 3. **Filtros Específicos por Entidad**

-   Cada repository implementa sus filtros únicos
-   Fácil mantenimiento y extensión

### 4. **API Más Limpia**

-   El repository recibe directamente el Request
-   No hay necesidad de callbacks complejos

---

## Ejemplos de Uso

### Crear Filtros para una Nueva Entidad

```php
class CompanyRepository extends BaseRepository
{
    protected function applySpecificFilters($query, Request $request)
    {
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->string('city') . '%');
        }

        if ($request->has('has_menu_items')) {
            $query->whereHas('menuCategories.menuItems');
        }
    }
}
```

### Controller Correspondiente

```php
class CompanyController extends CRUDController
{
    protected $resourceClass = CompanyResource::class;

    protected function getIndexEagerLoadRelations(): array
    {
        return ['menuCategories']; // Solo relaciones
    }
}
```

---

## Filtros Disponibles

### Filtros Comunes (Todos los Repositories)

```
?search=pizza                     # Búsqueda en campos searchable
?sort_by=created_at&sort_direction=desc  # Ordenamiento
?created_after=2025-01-01         # Filtros temporales
?created_before=2025-12-31
?ids=1,2,3                        # Filtros por IDs específicos
```

### Filtros Específicos (MenuItemRepository)

```
?available=true                   # Items disponibles
?min_price=15&max_price=30       # Rango de precio
?price_range=medium              # Categoría de precio (budget/medium/premium)
?vegetarian=true                 # Filtros dietéticos
?vegan=true
?gluten_free=true
?spicy=true
?category_id=1,2,3               # Filtros por categorías
?company_id=1                    # Filtros por empresa
?active_categories_only=true     # Solo categorías activas
?active_companies_only=true      # Solo empresas activas
```

### Paginación

```
?paginate=true&perPage=20&page=2  # Paginación
```

---

## Resultado

Ahora tienes una arquitectura limpia donde:

1. **El Controller** solo se preocupa por la presentación
2. **El Repository** maneja toda la lógica de filtros
3. **Los filtros** son automáticos y extensibles
4. **La API** es consistente y fácil de usar

```bash
# Ejemplo de uso completo
GET /menu-items?paginate=true&available=true&vegetarian=true&min_price=15&category_id=2&sort_by=price&sort_direction=asc
```

Esta separación hace que el código sea más mantenible, testeable y escalable.
