<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Facades\CurrentCompany;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $relations = [])
    {
        $query = $this->model->newQuery();

        // Apply company filter automatically if the model has company_id
        $this->applyCompanyScope($query);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    public function paginate(int $perPage = 15, array $relations = [])
    {
        $query = $this->model->newQuery();

        // Apply company filter automatically
        $this->applyCompanyScope($query);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    public function allWithFilters(Request $request, array $relations = [])
    {
        $query = $this->model->newQuery();

        // Apply company filter automatically
        $this->applyCompanyScope($query);

        if (!empty($relations)) {
            $query->with($relations);
        }

        $this->applyFilters($query, $request);

        return $query->get();
    }

    public function paginateWithFilters(Request $request, int $perPage = 15, array $relations = [])
    {
        $query = $this->model->newQuery();

        // Apply company filter automatically
        $this->applyCompanyScope($query);

        if (!empty($relations)) {
            $query->with($relations);
        }

        $this->applyFilters($query, $request);

        return $query->paginate($perPage);
    }

    public function find($id, array $relations = [])
    {
        $query = $this->model->newQuery();

        // Apply company filter automatically
        $this->applyCompanyScope($query);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function create(array $data)
    {
        // Automatically add company_id if the model has company_id column and it's not already set
        $table = $this->model->getTable();
        $hasCompanyId = Schema::hasColumn($table, 'company_id');

        if ($hasCompanyId && !isset($data['company_id']) && CurrentCompany::exists()) {
            $data['company_id'] = CurrentCompany::id();
        }

        return $this->model->create($data);
    }
    public function update($id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
        }
        return $record;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        if ($record) {
            $record->delete();
        }
        return $record;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Apply filters to the query.
     * This method applies both common and specific filters.
     */
    protected function applyFilters($query, Request $request)
    {
        // Aplicar filtros comunes que todos los repositorios pueden usar
        $this->applyCommonFilters($query, $request);

        // Aplicar filtros específicos (debe ser implementado por cada repositorio hijo)
        $this->applySpecificFilters($query, $request);
    }

    /**
     * Apply common filters that all repositories can use
     */
    protected function applyCommonFilters($query, Request $request)
    {
        // Filtro de búsqueda general (busca en campos searchable del modelo)
        if ($request->has('search') && method_exists($this->model, 'getSearchableFields')) {
            $searchTerm = $request->string('search');
            $searchableFields = $this->model->getSearchableFields();

            if (!empty($searchableFields) && !empty($searchTerm)) {
                $query->where(function ($q) use ($searchableFields, $searchTerm) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', '%' . $searchTerm . '%');
                    }
                });
            }
        }

        // Filtro de ordenamiento
        if ($request->has('sort_by')) {
            $sortBy = $request->string('sort_by');
            $sortDirection = $request->string('sort_direction', 'asc');

            // Validar dirección
            $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc'])
                ? strtolower($sortDirection)
                : 'asc';

            $query->orderBy($sortBy, $sortDirection);
        } else {
            // Ordenamiento por defecto
            $query->orderBy('created_at', 'desc');
        }

        // Filtro por fecha de creación
        if ($request->has('created_after')) {
            $query->where('created_at', '>=', $request->date('created_after'));
        }

        if ($request->has('created_before')) {
            $query->where('created_at', '<=', $request->date('created_before'));
        }

        // Filtro por IDs específicos
        if ($request->has('ids')) {
            $ids = collect(explode(',', $request->string('ids')))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->toArray();

            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }
    }

    /**
     * Apply company scope automatically if the model has company_id column
     */
    protected function applyCompanyScope($query)
    {
        // Check if the model has a company_id column
        $table = $this->model->getTable();
        $hasCompanyId = Schema::hasColumn($table, 'company_id');

        if ($hasCompanyId) {
            $companyId = $this->getCurrentCompanyId();

            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        }

        return $query;
    }

    /**
     * Get current company ID from cache or session (bypass CurrentCompany service)
     */
    protected function getCurrentCompanyId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Try cache first (for API requests)
        $cacheKey = "user_current_company.{$user->id}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try session (for web requests)  
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }

        // Fallback: get first company the user belongs to
        $userCompanies = $user->companies;
        if ($userCompanies->count() > 0) {
            $firstCompanyId = $userCompanies->first()->id;

            // Cache it for future requests
            Cache::put($cacheKey, $firstCompanyId, now()->addDays(30));

            return $firstCompanyId;
        }

        return null;
    }
    /**
     * Apply specific filters for each repository.
     * This method should be overridden by child repositories.
     */
    protected function applySpecificFilters($query, Request $request)
    {
        // Implementar en cada repositorio hijo
    }
}
