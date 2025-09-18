# Guía de Desarrollo: Sistema de Company Switching por Slug

## Resumen

Este documento explica cómo usar el sistema de company switching por slug para desarrollo frontend. Este sistema permite a los desarrolladores cambiar fácilmente entre diferentes empresas/restaurantes durante el desarrollo usando identificadores semánticos (slugs) en lugar de IDs numéricos.

## ¿Por qué usar slugs en lugar de IDs?

### Ventajas del sistema de slugs:

1. **Legibilidad**: `pizza-place` es más claro que `123`
2. **Estabilidad**: Los slugs no cambian entre entornos (dev/staging/prod)
3. **Tipado fuerte**: TypeScript puede validar slugs válidos
4. **Debugging**: Más fácil identificar problemas en logs
5. **URLs amigables**: Útil para compartir estados específicos

## Endpoints Disponibles

### 1. Switch por ID (Método original)

```http
POST /api/company/switch
Content-Type: application/json
Authorization: Bearer {token}

{
  "company_id": 123
}
```

### 2. Switch por Slug (Nuevo método)

```http
POST /api/company/switch-by-slug
Content-Type: application/json
Authorization: Bearer {token}

{
  "slug": "pizza-place"
}
```

### 3. Lista de Companies para Desarrollo

```http
GET /api/company/development-list
Authorization: Bearer {token}
```

Respuesta:

```json
{
    "companies": [
        {
            "id": 1,
            "name": "Mario's Pizza",
            "slug": "pizza-place",
            "is_active": true
        },
        {
            "id": 2,
            "name": "Burger Joint",
            "slug": "burger-joint",
            "is_active": true
        }
    ]
}
```

## Implementación Frontend (React + TypeScript)

### 1. Instalación de archivos

Copia estos archivos a tu proyecto React:

```
src/
  types/
    company.ts          # Tipos y enums de companies
  hooks/
    useCompanySwitch.ts # Hook para switching
  components/
    CompanySwitcher.tsx # Componente de desarrollo
```

### 2. Configuración de tipos

Actualiza el enum `CompanySlug` en `types/company.ts` con los slugs reales de tu base de datos:

```typescript
export enum CompanySlug {
    RESTAURANT_DEMO = "restaurant-demo",
    PIZZA_PLACE = "pizza-place",
    BURGER_JOINT = "burger-joint",
    // Agrega tus slugs reales aquí
}
```

### 3. Implementación del API Service

```typescript
// services/apiService.ts
class ApiService {
    private baseURL =
        process.env.REACT_APP_API_URL || "http://localhost:8000/api";
    private token = localStorage.getItem("auth_token");

    async post<T>(url: string, data: any): Promise<T> {
        const response = await fetch(`${this.baseURL}${url}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${this.token}`,
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    async get<T>(url: string): Promise<T> {
        const response = await fetch(`${this.baseURL}${url}`, {
            headers: {
                Authorization: `Bearer ${this.token}`,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }
}

export const apiService = new ApiService();
```

### 4. Uso del Hook

```typescript
// components/MyComponent.tsx
import React from "react";
import { useCompanySwitch } from "../hooks/useCompanySwitch";
import { CompanySlug } from "../types/company";
import { apiService } from "../services/apiService";

export function MyComponent() {
    const { switchCompany, isLoading, error, currentCompany } =
        useCompanySwitch({
            apiService,
            onSuccess: (response) => {
                console.log("Switched to:", response.company.name);
                // Actualizar estado global, refrescar datos, etc.
            },
            onError: (error) => {
                console.error("Switch failed:", error);
            },
        });

    const handleQuickSwitch = async () => {
        await switchCompany(CompanySlug.PIZZA_PLACE);
    };

    return (
        <div>
            <button onClick={handleQuickSwitch} disabled={isLoading}>
                Switch to Pizza Place
            </button>
            {error && <p>Error: {error}</p>}
            {currentCompany && <p>Current: {currentCompany.name}</p>}
        </div>
    );
}
```

### 5. Toolbar de Desarrollo

```typescript
// components/DevelopmentToolbar.tsx
import React from "react";
import { DevelopmentToolbar } from "./CompanySwitcher";
import { apiService } from "../services/apiService";

export function App() {
    return (
        <div>
            {/* Tu aplicación */}
            <main>{/* Contenido principal */}</main>

            {/* Toolbar solo en desarrollo */}
            {process.env.NODE_ENV === "development" && (
                <DevelopmentToolbar apiService={apiService} />
            )}
        </div>
    );
}
```

## Flujo de Desarrollo

### 1. Configuración inicial

1. Obtén la lista de companies disponibles:

    ```bash
    curl -H "Authorization: Bearer YOUR_TOKEN" \
         http://localhost:8000/api/company/development-list
    ```

2. Actualiza el enum `CompanySlug` con los slugs reales

3. Agrega el componente `DevelopmentToolbar` a tu app

### 2. Desarrollo de features

1. **Selecciona una company** usando el toolbar
2. **Desarrolla tu feature** normalmente
3. **Prueba con diferentes companies** usando el switcher
4. **Valida que todo funcione** independientemente de la company

### 3. Testing entre companies

```typescript
// tests/companySwitch.test.ts
import { switchToCompany } from "../utils/testHelpers";
import { CompanySlug } from "../types/company";

describe("Feature X across companies", () => {
    test("works for pizza place", async () => {
        await switchToCompany(CompanySlug.PIZZA_PLACE);
        // tus tests aquí
    });

    test("works for burger joint", async () => {
        await switchToCompany(CompanySlug.BURGER_JOINT);
        // tus tests aquí
    });
});
```

## URL Management (Avanzado)

Para persistir la company seleccionada en la URL:

```typescript
// hooks/useCompanyFromURL.ts
import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useCompanySwitch } from './useCompanySwitch';
import { CompanySlug, isValidCompanySlug } from '../types/company';

export function useCompanyFromURL() {
  const location = useLocation();
  const navigate = useNavigate();
  const { switchCompany } = useCompanySwitch({...});

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const companySlug = params.get('company');

    if (companySlug && isValidCompanySlug(companySlug)) {
      switchCompany(companySlug as CompanySlug);
    }
  }, [location.search]);

  const setCompanyInURL = (slug: CompanySlug) => {
    const params = new URLSearchParams(location.search);
    params.set('company', slug);
    navigate(`${location.pathname}?${params.toString()}`);
  };

  return { setCompanyInURL };
}
```

Uso:

```
http://localhost:3000/dashboard?company=pizza-place
```

## Consideraciones de Seguridad

1. **Validación en backend**: Siempre valida que el usuario tenga acceso a la company
2. **Solo desarrollo**: Considera deshabilitar el switch libre en producción
3. **Logs de auditoría**: Registra los cambios de company para debugging

## Troubleshooting

### Error: "You do not have access to this company"

-   Verifica que tu usuario tenga acceso a esa company
-   Revisa la tabla `company_users` en la base de datos

### Error: "Company not found"

-   Verifica que el slug existe en la base de datos
-   Actualiza el enum `CompanySlug` con slugs válidos

### El contexto no cambia después del switch

-   Verifica que el middleware `company.scoped` esté funcionando
-   Revisa que la sesión se esté actualizando correctamente

## Comandos Útiles

```bash
# Ver companies disponibles
php artisan tinker
>>> App\Models\Company::select('id', 'name', 'slug')->get();

# Crear un slug para una company existente
>>> $company = App\Models\Company::find(1);
>>> $company->slug = Str::slug($company->name);
>>> $company->save();

# Verificar acceso de usuario a company
>>> $user = App\Models\User::find(1);
>>> $user->companies()->get();
```

## Próximos Pasos

1. **Implementar en tu frontend** siguiendo esta guía
2. **Agregar más validaciones** si es necesario
3. **Crear tests automatizados** para el switching
4. **Documentar company-specific features** que desarrolles
5. **Considerar un sistema de favoritos** para companies frecuentes

---

¿Tienes preguntas sobre la implementación? Revisa los archivos de ejemplo o consulta con el equipo de backend.
