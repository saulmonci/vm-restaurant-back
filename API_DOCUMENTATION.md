# 📚 Documentación de la API - VM Restaurant Backend

## 🔗 URL Base

```
http://localhost/api
```

## 🔐 Autenticación

Esta API utiliza **Laravel Sanctum** para autenticación mediante tokens Bearer.

### Headers requeridos para endpoints protegidos:

```http
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 🚀 Endpoints de Autenticación

### 1. 🔑 Login

Autentica un usuario y devuelve un token de acceso.

```http
POST /api/auth/login
Content-Type: application/json
```

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "access_token": "1|abcd1234efgh5678ijkl9012mnop3456qrst7890",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "user@example.com",
        "display_name": "Juanito",
        "timezone": "America/Mexico_City",
        "preferred_language": "es",
        "preferred_currency": "MXN"
    },
    "companies": [
        {
            "id": 1,
            "name": "Restaurante La Plaza",
            "address": "Calle Principal 123"
        },
        {
            "id": 2,
            "name": "Café Central",
            "address": "Avenida Central 456"
        }
    ]
}
```

**Response (422) - Credenciales incorrectas:**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Las credenciales proporcionadas son incorrectas."]
    }
}
```

---

### 2. 📝 Registro

Registra un nuevo usuario.

```http
POST /api/auth/register
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**

```json
{
    "access_token": "1|abcd1234efgh5678ijkl9012mnop3456qrst7890",
    "token_type": "Bearer",
    "user": {
        "id": 2,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "created_at": "2025-09-16T10:30:00.000000Z"
    },
    "companies": []
}
```

---

### 3. 👤 Usuario Actual

Obtiene información del usuario autenticado.

```http
GET /api/auth/user
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "user@example.com",
        "display_name": "Juanito",
        "timezone": "America/Mexico_City"
    },
    "companies": [
        {
            "id": 1,
            "name": "Restaurante La Plaza"
        }
    ]
}
```

---

### 4. 🚪 Logout

Cierra la sesión del usuario (invalida el token actual).

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "message": "Successfully logged out"
}
```

---

### 5. 🔄 Renovar Token

Genera un nuevo token de acceso.

```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "access_token": "2|new_token_here",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "user@example.com"
    }
}
```

---

## 🏢 Endpoints de Gestión de Compañías

### 1. 🏢 Compañía Actual

Obtiene información de la compañía actualmente seleccionada.

```http
GET /api/company/current
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "id": 1,
    "name": "Restaurante La Plaza",
    "address": "Calle Principal 123",
    "email": "info@laplaza.com",
    "phone": "+52 55 1234 5678",
    "settings": {
        "theme": "blue",
        "timezone": "America/Mexico_City",
        "features": ["analytics", "reporting"]
    }
}
```

**Response (404) - Sin compañía seleccionada:**

```json
{
    "message": "No company context found"
}
```

---

### 2. 📋 Compañías del Usuario

Lista todas las compañías a las que el usuario tiene acceso.

```http
GET /api/company/user-companies
Authorization: Bearer {token}
```

**Response (200):**

```json
[
    {
        "id": 1,
        "name": "Restaurante La Plaza",
        "address": "Calle Principal 123",
        "email": "info@laplaza.com"
    },
    {
        "id": 2,
        "name": "Café Central",
        "address": "Avenida Central 456",
        "email": "contacto@cafecentral.com"
    }
]
```

---

### 3. 🔄 Cambiar Compañía

Cambia el contexto de compañía actual del usuario.

```http
POST /api/company/switch
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "company_id": 2
}
```

**Response (200):**

```json
{
    "success": true,
    "company": {
        "id": 2,
        "name": "Café Central",
        "settings": {
            "theme": "green",
            "timezone": "America/Mexico_City"
        }
    }
}
```

**Response (403) - Sin acceso a la compañía:**

```json
{
    "success": false,
    "message": "No tienes acceso a esta compañía"
}
```

---

### 4. ⚙️ Actualizar Configuración

Actualiza la configuración de la compañía actual.

```http
PUT /api/company/settings
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "theme": "dark",
    "notifications": {
        "email": true,
        "push": false
    },
    "business_hours": {
        "open": "08:00",
        "close": "22:00"
    }
}
```

**Response (200):**

```json
{
    "success": true,
    "settings": {
        "theme": "dark",
        "timezone": "America/Mexico_City",
        "notifications": {
            "email": true,
            "push": false
        },
        "business_hours": {
            "open": "08:00",
            "close": "22:00"
        }
    }
}
```

---

## 📋 Endpoints de Menú (Scoped por Compañía)

### 1. 📂 Categorías de Menú

#### Listar Categorías

```http
GET /api/menu-categories
Authorization: Bearer {token}
```

**Query Parameters:**

-   `search` (opcional): Buscar por nombre
-   `page` (opcional): Número de página (default: 1)
-   `per_page` (opcional): Items por página (default: 15)

**Response (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Entradas",
            "description": "Platillos para comenzar",
            "sort_order": 1,
            "is_active": true,
            "items_count": 5
        },
        {
            "id": 2,
            "name": "Platos Principales",
            "description": "Platillos principales",
            "sort_order": 2,
            "is_active": true,
            "items_count": 12
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1
    }
}
```

#### Crear Categoría

```http
POST /api/menu-categories
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "Bebidas",
    "description": "Bebidas calientes y frías",
    "sort_order": 3,
    "is_active": true
}
```

**Response (201):**

```json
{
    "id": 3,
    "name": "Bebidas",
    "description": "Bebidas calientes y frías",
    "sort_order": 3,
    "is_active": true,
    "company_id": 1,
    "created_at": "2025-09-16T10:30:00.000000Z"
}
```

#### Obtener Categoría

```http
GET /api/menu-categories/{id}
Authorization: Bearer {token}
```

#### Actualizar Categoría

```http
PUT /api/menu-categories/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

#### Eliminar Categoría

```http
DELETE /api/menu-categories/{id}
Authorization: Bearer {token}
```

---

### 2. 🍽️ Items de Menú

#### Listar Items

```http
GET /api/menu-items
Authorization: Bearer {token}
```

**Query Parameters:**

-   `search` (opcional): Buscar por nombre o descripción
-   `category_id` (opcional): Filtrar por categoría
-   `is_available` (opcional): Filtrar por disponibilidad (true/false)
-   `page` (opcional): Número de página
-   `per_page` (opcional): Items por página

**Response (200):**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Hamburguesa Especial",
            "description": "Hamburguesa de carne de res con queso cheddar",
            "price": 189.0,
            "image_url": "/storage/menu/hamburger.jpg",
            "is_available": true,
            "category": {
                "id": 2,
                "name": "Platos Principales"
            },
            "allergens": ["gluten", "lactosa"],
            "preparation_time": 15
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 25,
        "last_page": 2
    }
}
```

#### Crear Item

```http
POST /api/menu-items
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "Tacos al Pastor",
    "description": "3 tacos de pastor con piña y cebolla",
    "price": 85.0,
    "category_id": 2,
    "is_available": true,
    "allergens": ["gluten"],
    "preparation_time": 10,
    "ingredients": ["tortilla", "carne de cerdo", "piña", "cebolla"]
}
```

**Response (201):**

```json
{
    "id": 2,
    "name": "Tacos al Pastor",
    "description": "3 tacos de pastor con piña y cebolla",
    "price": 85.0,
    "category_id": 2,
    "is_available": true,
    "company_id": 1,
    "created_at": "2025-09-16T10:30:00.000000Z"
}
```

---

## 🔍 Endpoints Públicos (Sin Autenticación)

### 1. 📋 Menú Público de una Compañía

```http
GET /api/public/companies/{companyId}/menu
```

**Response (200):**

```json
{
    "company": {
        "id": 1,
        "name": "Restaurante La Plaza",
        "address": "Calle Principal 123"
    },
    "categories": [
        {
            "id": 1,
            "name": "Entradas",
            "items": [
                {
                    "id": 1,
                    "name": "Nachos Especiales",
                    "price": 65.0,
                    "description": "Nachos con queso y jalapeños"
                }
            ]
        }
    ]
}
```

---

## 🚨 Códigos de Error Comunes

### 401 - No Autorizado

```json
{
    "message": "Unauthenticated."
}
```

### 403 - Prohibido

```json
{
    "message": "No tienes acceso a este recurso."
}
```

### 404 - No Encontrado

```json
{
    "message": "El recurso solicitado no fue encontrado."
}
```

### 422 - Error de Validación

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["El campo nombre es obligatorio."],
        "email": ["El formato del email es inválido."]
    }
}
```

### 500 - Error del Servidor

```json
{
    "message": "Error interno del servidor."
}
```

---

## 📱 Implementación en App Móvil

### 1. 🔐 Flujo de Autenticación

```javascript
// 1. Login
const loginResponse = await fetch("/api/auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
});

const { access_token, user, companies } = await loginResponse.json();

// 2. Guardar token
await AsyncStorage.setItem("auth_token", access_token);
await AsyncStorage.setItem("user", JSON.stringify(user));
await AsyncStorage.setItem("companies", JSON.stringify(companies));
```

### 2. 🏢 Selección de Compañía

```javascript
// Si el usuario tiene múltiples compañías, mostrar selector
if (companies.length > 1) {
    // Mostrar lista de compañías para seleccionar
    const selectedCompany = await showCompanySelector(companies);

    // Cambiar a la compañía seleccionada
    await fetch("/api/company/switch", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ company_id: selectedCompany.id }),
    });
}
```

### 3. 📋 Obtener Datos del Menú

```javascript
// Obtener categorías
const categoriesResponse = await fetch("/api/menu-categories", {
    headers: { Authorization: `Bearer ${token}` },
});
const categories = await categoriesResponse.json();

// Obtener items por categoría
const itemsResponse = await fetch(`/api/menu-items?category_id=${categoryId}`, {
    headers: { Authorization: `Bearer ${token}` },
});
const items = await itemsResponse.json();
```

### 4. 🔄 Manejo de Errores

```javascript
const response = await fetch("/api/endpoint", {
    headers: { Authorization: `Bearer ${token}` },
});

if (response.status === 401) {
    // Token expirado, redirigir a login
    await AsyncStorage.removeItem("auth_token");
    navigateToLogin();
} else if (response.status === 403) {
    // Sin permisos, mostrar mensaje
    showErrorMessage("No tienes permisos para esta acción");
}
```

---

## 🛠️ Notas Técnicas

### Middleware Aplicado

-   **auth:sanctum**: Verifica que el usuario esté autenticado
-   **company.scoped**: Filtra automáticamente los datos por la compañía actual

### Paginación

Todos los endpoints de listado soportan paginación con los parámetros:

-   `page`: Número de página (default: 1)
-   `per_page`: Items por página (default: 15, máximo: 100)

### Búsqueda

Los endpoints de listado soportan búsqueda con el parámetro `search` que busca en los campos principales del modelo.

### Filtros

Cada endpoint puede tener filtros específicos documentados en su sección correspondiente.

---

## 📞 Soporte

Para dudas o problemas con la API, contacta al equipo de desarrollo.

**Versión de la API:** 1.0  
**Última actualización:** 16 de Septiembre, 2025
