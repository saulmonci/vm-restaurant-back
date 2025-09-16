# Documentación de Paginación API

## Uso de Paginación en los Controllers

Todos los controllers que extienden de `CRUDController` ahora soportan paginación automáticamente.

### Parámetros de Paginación

| Parámetro  | Tipo    | Default | Descripción                                   |
| ---------- | ------- | ------- | --------------------------------------------- |
| `paginate` | boolean | `false` | Activa/desactiva la paginación                |
| `perPage`  | integer | `15`    | Número de items por página (min: 1, max: 100) |
| `page`     | integer | `1`     | Número de página actual                       |

### Ejemplos de Uso

#### 1. Sin Paginación (Comportamiento por defecto)

```
GET /menu-items
```

**Respuesta:**

```json
{
  "data": [...],
  "meta": {
    "paginated": false,
    "total_items": 25,
    "filters_applied": false
  },
  "request_info": {
    "url": "http://localhost/api/menu-items",
    "query_params": {}
  }
}
```

#### 2. Con Paginación Básica

```
GET /menu-items?paginate=true
```

**Respuesta:**

```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45,
    "from": 1,
    "to": 15,
    "has_more_pages": true,
    "prev_page_url": null,
    "next_page_url": "http://localhost/api/menu-items?page=2",
    "path": "http://localhost/api/menu-items"
  },
  "meta": {
    "paginated": true,
    "per_page_requested": 15,
    "page_requested": 1,
    "filters_applied": false,
    "total_pages": 3
  }
}
```

#### 3. Paginación Personalizada

```
GET /menu-items?paginate=true&perPage=10&page=2
```

#### 4. Con Filtros y Paginación (MenuItems)

```
GET /menu-items?paginate=true&perPage=5&category_id=1&available=true&min_price=10&max_price=50&vegetarian=true&search=pizza
```

### Filtros Disponibles por Controller

#### MenuItemController

-   `category_id` - Filtrar por categoría
-   `available` - Filtrar por disponibilidad
-   `min_price` / `max_price` - Rango de precios
-   `search` - Buscar en nombre y descripción
-   `vegetarian` - Solo items vegetarianos
-   `vegan` - Solo items veganos
-   `gluten_free` - Solo items sin gluten
-   `sort_by` - Campo para ordenar (name, price, created_at)
-   `sort_direction` - Dirección del ordenamiento (asc, desc)

#### Ejemplo Completo con Filtros:

```
GET /menu-items?paginate=true&perPage=8&page=1&category_id=2&available=true&min_price=15&max_price=35&search=hamburguesa&vegetarian=false&sort_by=price&sort_direction=asc&include_permissions=true&include_pricing=true&currency=EUR
```

### Estructura de Respuesta

#### Con Paginación:

```json
{
  "data": [...], // Array de recursos
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67,
    "from": 1,
    "to": 15,
    "has_more_pages": true,
    "prev_page_url": null,
    "next_page_url": "...",
    "path": "..."
  },
  "meta": {
    "paginated": true,
    "per_page_requested": 15,
    "page_requested": 1,
    "filters_applied": true,
    "total_pages": 5
  },
  "request_info": {
    "url": "...",
    "query_params": {...}
  }
}
```

#### Sin Paginación:

```json
{
  "data": [...], // Array de recursos
  "meta": {
    "paginated": false,
    "total_items": 25,
    "filters_applied": false
  },
  "request_info": {
    "url": "...",
    "query_params": {...}
  }
}
```

### Implementación en Controllers Personalizados

Para agregar filtros personalizados a tus controllers:

```php
class MenuItemController extends CRUDController
{
    protected function getFilterParameters(Request $request): array
    {
        $filters = [];

        if ($request->has('category_id')) {
            $filters['category_id'] = $request->integer('category_id');
        }

        // ... más filtros

        return $filters;
    }

    protected function applyFilters($query, array $filters)
    {
        // Implementar en el Repository específico
        return $query;
    }
}
```

### Notas Importantes

1. **Límites de Paginación**: El `perPage` está limitado entre 1 y 100 items
2. **Performance**: Usa sempre `eager loading` para evitar N+1 queries
3. **Filtros**: Los filtros requieren implementación en el Repository específico
4. **URLs**: Las URLs de paginación incluyen automáticamente los parámetros de filtro
5. **Validación**: Los parámetros se validan automáticamente (integers, booleans, etc.)
