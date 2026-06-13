<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index()
    {
        return view('panel.settings.permissions');
    }

    public function data(Request $request)
    {
        $query = Permission::withCount(['roles', 'users']);

        if ($request->filled('guard')) {
            $query->where('guard_name', $request->guard);
        }

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        return DataTables::of($query)->make(true);
    }

    /** Return all permission groups */
    public function groups()
    {
        $groups = \App\Models\PermissionGroup::orderBy('name')->get(['id', 'name']);
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $data = $this->validatePermission($request);

        $permission = Permission::create($data);

        ActivityLogger::log('created', $permission, null, $permission->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully.',
            'data'    => ['id' => $permission->id, 'name' => $permission->name, 'group' => $permission->group]
        ]);
    }

    public function show(Permission $permission)
    {
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $this->validatePermission($request, $permission->id);

        $old = $permission->getAttributes();
        $permission->update($data);

        ActivityLogger::log('updated', $permission, $old, $permission->getAttributes());

        return response()->json(['success' => true, 'message' => 'Permission updated successfully.']);
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0 || $permission->users()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete permission that is assigned to roles or users.'], 422);
        }

        $snapshot = $permission->getAttributes();
        $permission->delete();
        ActivityLogger::log('deleted', $permission, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Permission deleted successfully.']);
    }

    private function validatePermission(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($ignoreId)],
            'guard_name' => ['required', 'string', 'in:web'],
            'group'      => ['nullable', 'string', 'max:100'],
        ]);
    }
}
