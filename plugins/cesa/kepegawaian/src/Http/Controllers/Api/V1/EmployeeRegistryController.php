<?php

namespace Cesa\Kepegawaian\Http\Controllers\Api\V1;

use Cesa\Kepegawaian\Http\Requests\Api\V1\EmployeeIndexRequest;
use Cesa\Kepegawaian\Http\Requests\Api\V1\ResolveEmployeeRequest;
use Cesa\Kepegawaian\Http\Resources\V1\RegistryEmployeeResource;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmployeeRegistryController
{
    public function index(EmployeeIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Employee::class);

        $filters = $request->validated('filter', []);

        $employees = Employee::query()
            ->with($this->registryRelations())
            ->when(
                filled($filters['employee_code'] ?? null),
                fn ($query) => $query->where('employee_code', 'like', '%'.$filters['employee_code'].'%')
            )
            ->when(
                filled($filters['name'] ?? null),
                fn ($query) => $query->where('name', 'like', '%'.$filters['name'].'%')
            )
            ->when(
                isset($filters['company_id']),
                fn ($query) => $query->where('company_id', $filters['company_id'])
            )
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', (bool) $filters['is_active'])
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return RegistryEmployeeResource::collection($employees);
    }

    public function show(Employee $employee): RegistryEmployeeResource
    {
        Gate::authorize('view', $employee);

        return new RegistryEmployeeResource(
            $employee->load($this->registryRelations())
        );
    }

    public function resolve(ResolveEmployeeRequest $request): RegistryEmployeeResource
    {
        Gate::authorize('viewAny', Employee::class);

        $identifier = EmployeeIdentifier::query()
            ->current()
            ->where('source_system', $request->string('source_system')->toString())
            ->where('source_instance', $request->string('source_instance')->toString())
            ->where('identifier_type', $request->string('identifier_type')->toString())
            ->where('normalized_value', $request->string('external_id')->lower()->toString())
            ->with(['employee' => fn ($query) => $query->with($this->registryRelations())])
            ->firstOrFail();

        Gate::authorize('view', $identifier->employee);

        return new RegistryEmployeeResource($identifier->employee);
    }

    /**
     * @return array<int|string, callable|string>
     */
    private function registryRelations(): array
    {
        return [
            'company:id,name,company_id',
            'department:id,name',
            'identifiers' => fn ($query) => $query
                ->current()
                ->orderBy('source_system')
                ->orderBy('source_instance')
                ->orderBy('identifier_type'),
        ];
    }
}
