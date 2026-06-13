<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index()
    {
        $permissions = Permission::all()->groupBy('guard_name');
        return view('panel.settings.roles', compact('permissions'));
    }

    public function data(Request $request)
    {
        $query = Role::withCount(['users', 'permissions']);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return DataTables::of($query)
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $role = Role::create($data);

        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        ActivityLogger::log('created', $role, null, $role->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data'    => ['id' => $role->id, 'name' => $role->name]
        ]);
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        return response()->json($role);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role->id);
        $data['is_active'] = $request->boolean('is_active', true);

        $old = $role->getAttributes();
        $role->update($data);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        ActivityLogger::log('updated', $role, $old, $role->getAttributes());

        return response()->json(['success' => true, 'message' => 'Role updated successfully.']);
    }

    public function destroy(Role $role)
    {
        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete role that is assigned to users.'], 422);
        }

        $snapshot = $role->getAttributes();
        $role->delete();
        ActivityLogger::log('deleted', $role, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
    }

    public function permissions(Role $role)
    {
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        // Group by permission group, then ungrouped last
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()
            ->groupBy(fn($p) => $p->group ?: 'Other');

        return response()->json([
            'role'            => $role,
            'rolePermissions' => $rolePermissions,
            'allPermissions'  => $allPermissions,
        ]);
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($request->permissions ?? []);
        $newPermissions = $role->fresh()->permissions->pluck('name')->toArray();

        ActivityLogger::log('updated', $role, ['permissions' => $oldPermissions], ['permissions' => $newPermissions]);

        return response()->json(['success' => true, 'message' => 'Role permissions updated successfully.']);
    }

    private function validateRole(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($ignoreId)],
            'guard_name' => ['required', 'string', 'in:web'],
            'is_active'  => ['nullable', 'boolean'],
        ]);
    }
}
