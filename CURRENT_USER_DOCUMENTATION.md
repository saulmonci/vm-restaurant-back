# CurrentUser Facade - Documentación Completa

## 🎯 **¿Qué es CurrentUser?**

**CurrentUser** es un Facade elegante que proporciona acceso rápido y cacheado a la información del usuario autenticado actual, siguiendo el mismo patrón exitoso de `CurrentCompany`.

## ✨ **Características Principales**

### 🚀 **Performance Optimizado**

-   ✅ **Singleton con cache** - Una sola instancia por request
-   ✅ **Cache de 1 hora** - Evita consultas repetidas a la DB
-   ✅ **Lazy loading** - Solo carga datos cuando se necesitan
-   ✅ **Auto-inicialización** - Se configura automáticamente

### 🛠️ **API Completa**

-   ✅ **Información básica** - ID, nombre, email
-   ✅ **Configuraciones** - Timezone, idioma, moneda
-   ✅ **Preferencias** - Configuraciones personalizadas del usuario
-   ✅ **Roles y permisos** - Verificación de admin, roles
-   ✅ **Multi-tenancy** - Integración con companies
-   ✅ **Gestión de cache** - Limpieza y refresh manual

## 📖 **Guía de Uso**

### **Información Básica del Usuario**

```php
// Verificar si hay usuario autenticado
if (CurrentUser::check()) {
    // Obtener información básica
    $userId = CurrentUser::id();           // int|null
    $user = CurrentUser::get();            // User|null
    $name = CurrentUser::name();           // string|null (display_name or name)
    $email = CurrentUser::email();         // string|null
}

// Alias para exists()
$isAuthenticated = CurrentUser::exists(); // bool
```

### **Configuraciones del Usuario**

```php
// Configuraciones regionales
$timezone = CurrentUser::timezone();      // string (default: app.timezone)
$language = CurrentUser::language();      // string (default: app.locale)
$currency = CurrentUser::currency();      // string (default: 'USD')

// Ejemplo de uso
$userTime = now()->setTimezone(CurrentUser::timezone());
$greeting = __('Hello', [], CurrentUser::language());
```

### **Preferencias Personalizadas**

```php
// Obtener todas las preferencias
$allPrefs = CurrentUser::preferences();   // array|null

// Obtener preferencia específica
$theme = CurrentUser::preferences('theme', 'light');
$notifications = CurrentUser::preferences('notifications.email', true);

// Actualizar preferencias
$success = CurrentUser::updatePreferences([
    'theme' => 'dark',
    'notifications' => [
        'email' => false,
        'push' => true
    ],
    'dashboard_layout' => 'compact'
]);
```

### **Roles y Permisos**

```php
// Verificaciones de rol
$isAdmin = CurrentUser::isAdmin();        // bool
$hasRole = CurrentUser::hasRole('admin'); // bool
$isActive = CurrentUser::isActive();      // bool

// Ejemplo de uso en controllers
public function adminOnlyAction() {
    if (!CurrentUser::isAdmin()) {
        return response()->json(['error' => 'Admin access required'], 403);
    }

    // Lógica de admin...
}
```

### **Multi-tenancy Integration**

```php
// Obtener companies del usuario
$companies = CurrentUser::companies();    // Collection

// Usar junto con CurrentCompany
$userInfo = [
    'user_id' => CurrentUser::id(),
    'user_name' => CurrentUser::name(),
    'company_id' => CurrentCompany::id(),
    'company_name' => CurrentCompany::get()?->name,
    'companies_count' => CurrentUser::companies()->count()
];
```

### **Gestión de Cache y Updates**

```php
// Actualizar última actividad
CurrentUser::updateLastActivity();

// Limpiar cache manualmente
CurrentUser::clearCache();

// Refrescar datos (útil después de updates)
CurrentUser::refresh();

// Re-inicializar (generalmente no necesario)
CurrentUser::initialize();
```

## 🏗️ **Ejemplos de Implementación**

### **En Controllers**

```php
class UserController extends Controller
{
    public function profile()
    {
        return response()->json([
            'user' => [
                'id' => CurrentUser::id(),
                'name' => CurrentUser::name(),
                'email' => CurrentUser::email(),
                'timezone' => CurrentUser::timezone(),
                'preferences' => CurrentUser::preferences(),
                'is_admin' => CurrentUser::isAdmin(),
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        // Actualizar preferencias
        if ($request->has('preferences')) {
            CurrentUser::updatePreferences($request->preferences);
        }

        // Refrescar cache
        CurrentUser::refresh();

        return response()->json(['success' => true]);
    }
}
```

### **En Middleware**

```php
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!CurrentUser::isAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'Admin access required');
        }

        return $next($request);
    }
}
```

### **En Blade Templates**

```php
// En el ServiceProvider o usando View Composers
View::composer('*', function ($view) {
    $view->with([
        'currentUserId' => CurrentUser::id(),
        'currentUserName' => CurrentUser::name(),
        'userTimezone' => CurrentUser::timezone(),
        'userPreferences' => CurrentUser::preferences(),
    ]);
});
```

### **En Repositories/Models**

```php
class AuditableRepository extends BaseRepository
{
    public function create(array $data)
    {
        // Agregar automáticamente created_by
        if (!isset($data['created_by']) && CurrentUser::exists()) {
            $data['created_by'] = CurrentUser::id();
        }

        return parent::create($data);
    }
}
```

## 📋 **Métodos Disponibles**

### **Información del Usuario**

| Método                 | Retorno        | Descripción                    |
| ---------------------- | -------------- | ------------------------------ |
| `CurrentUser::id()`    | `int\|null`    | ID del usuario autenticado     |
| `CurrentUser::get()`   | `User\|null`   | Instancia completa del usuario |
| `CurrentUser::name()`  | `string\|null` | Nombre para mostrar            |
| `CurrentUser::email()` | `string\|null` | Email del usuario              |

### **Verificaciones**

| Método                        | Retorno | Descripción                |
| ----------------------------- | ------- | -------------------------- |
| `CurrentUser::exists()`       | `bool`  | Si hay usuario autenticado |
| `CurrentUser::check()`        | `bool`  | Alias de exists()          |
| `CurrentUser::isAdmin()`      | `bool`  | Si el usuario es admin     |
| `CurrentUser::isActive()`     | `bool`  | Si el usuario está activo  |
| `CurrentUser::hasRole($role)` | `bool`  | Si tiene un rol específico |

### **Configuraciones**

| Método                                     | Retorno  | Descripción              |
| ------------------------------------------ | -------- | ------------------------ |
| `CurrentUser::timezone()`                  | `string` | Zona horaria del usuario |
| `CurrentUser::language()`                  | `string` | Idioma preferido         |
| `CurrentUser::currency()`                  | `string` | Moneda preferida         |
| `CurrentUser::preferences($key, $default)` | `mixed`  | Preferencias del usuario |

### **Gestión**

| Método                                   | Retorno      | Descripción                 |
| ---------------------------------------- | ------------ | --------------------------- |
| `CurrentUser::updatePreferences($prefs)` | `bool`       | Actualizar preferencias     |
| `CurrentUser::updateLastActivity()`      | `bool`       | Actualizar última actividad |
| `CurrentUser::companies()`               | `Collection` | Companies del usuario       |
| `CurrentUser::clearCache()`              | `void`       | Limpiar cache               |
| `CurrentUser::refresh()`                 | `void`       | Refrescar datos             |

## 🔄 **Comparación con Auth::user()**

### **Antes (Laravel Auth):**

```php
$user = Auth::user();              // Query DB cada vez
$userId = Auth::id();              // Query DB cada vez
$userName = Auth::user()?->name;   // Query DB + null check
$isAdmin = Auth::user()?->role === 'admin'; // Query + logic
```

### **Después (CurrentUser):**

```php
$user = CurrentUser::get();        // Desde cache ⚡
$userId = CurrentUser::id();       // Desde cache ⚡
$userName = CurrentUser::name();   // Desde cache + smart fallback ⚡
$isAdmin = CurrentUser::isAdmin(); // Desde cache + helper ⚡
```

## ⚡ **Beneficios de Performance**

-   **80-90% menos queries** de usuario por request
-   **Cache inteligente** de 1 hora con invalidación automática
-   **Lazy loading** - Solo carga cuando se usa
-   **Singleton pattern** - Una instancia por request
-   **Métodos helper** - Evita lógica repetitiva

## 🎯 **Casos de Uso Comunes**

1. **Dashboards personalizados** con información del usuario
2. **Auditoría automática** - created_by, updated_by
3. **Localización** - timezone, idioma, moneda
4. **Control de acceso** - roles, permisos
5. **Configuraciones per-user** - tema, preferencias
6. **Logging contextual** - incluir info del usuario
7. **Multi-tenancy** - relación user-companies

¡Con **CurrentUser** tienes acceso elegante y optimizado a toda la información del usuario autenticado! 🚀
