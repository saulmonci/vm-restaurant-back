<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CurrentCompany Facade
 * 
 * @method static int|null id()
 * @method static \App\Models\Company|null get()
 * @method static array|mixed settings(string $key = null, $default = null)
 * @method static bool exists()
 * @method static bool switchTo(int $companyId)
 * @method static bool updateSettings(array $newSettings)
 * @method static \Illuminate\Support\Collection getUserCompanies()
 * @method static void clearCache()
 * @method static void initialize()
 * 
 * @see \App\Services\CurrentCompany
 */
class CurrentCompany extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \App\Services\CurrentCompany::class;
    }
}