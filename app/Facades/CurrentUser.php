<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CurrentUser Facade
 * 
 * @method static int|null id()
 * @method static \App\Models\User|null get()
 * @method static bool exists()
 * @method static bool check()
 * @method static array|mixed preferences(string $key = null, $default = null)
 * @method static string|null name()
 * @method static string|null email()
 * @method static string timezone()
 * @method static string language()
 * @method static string currency()
 * @method static bool hasRole(string $role)
 * @method static bool isAdmin()
 * @method static bool isActive()
 * @method static bool updatePreferences(array $newPreferences)
 * @method static bool updateLastActivity()
 * @method static \Illuminate\Support\Collection companies()
 * @method static void clearCache()
 * @method static void refresh()
 * @method static void initialize()
 * 
 * @see \App\Services\CurrentUser
 */
class CurrentUser extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \App\Services\CurrentUser::class;
    }
}
