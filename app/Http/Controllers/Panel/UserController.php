<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('panel.settings.users', compact('roles'));
    }

    public function data(Request $request)
    {
        $query = User::with(['parent', 'creator', 'roles'])
            ->select(['id', 'name', 'email', 'phone', 'designation', 'department', 'parent_id', 'is_active', 'created_by', 'created_at']);

        // Filter by user type
        if ($request->filled('type')) {
            if ($request->type === 'main') {
                $query->whereNull('parent_id');
            } elseif ($request->type === 'sub') {
                $query->whereNotNull('parent_id');
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return DataTables::of($query)
            ->addColumn('user_type', function ($user) {
                return $user->isMainUser() ? 'Main User' : 'Sub User';
            })
            ->addColumn('parent_name', function ($user) {
                return $user->parent ? $user->parent->name : '-';
            })
            ->addColumn('roles_list', function ($user) {
                return $user->roles->pluck('name')->join(', ') ?: 'No roles assigned';
            })
            ->addColumn('status', function ($user) {
                return $user->is_active 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($user) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(' . $user->id . ')">Edit</button>';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-info" onclick="manageRoles(' . $user->id . ')">Roles</button>';
                if ($user->isMainUser()) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewSubUsers(' . $user->id . ')">Sub Users</button>';
                }
                $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(' . $user->id . ')">Delete</button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = auth()->id();

        $user = User::create($data);

        // Assign roles if provided — resolve by ID since frontend sends IDs
        if ($request->has('roles')) {
            $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->roles ?? [])->get();
            $user->syncRoles($roles);
        }

        ActivityLogger::log('created', $user, null, $user->getAttributes());

        return response()->json([
            'success' => true, 
            'message' => 'User created successfully.',
            'data' => ['id' => $user->id, 'name' => $user->name]
        ]);
    }

    public function show(User $user)
    {
        $user->load(['parent', 'creator', 'roles', 'permissions']);
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user->id);
        
        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $old = $user->getAttributes();
        $user->update($data);

        // Update roles if provided — resolve by ID since frontend sends IDs
        if ($request->has('roles')) {
            $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->roles ?? [])->get();
            $user->syncRoles($roles);
        }

        ActivityLogger::log('updated', $user, $old, $user->getAttributes());

        return response()->json(['success' => true, 'message' => 'User updated successfully.']);
    }

    public function destroy(User $user)
    {
        // Prevent deleting the current user
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        // Check if user has sub-users
        if ($user->subUsers()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete user with sub-users. Please delete or reassign sub-users first.'], 422);
        }

        $snapshot = $user->getAttributes();
        $user->delete();
        ActivityLogger::log('deleted', $user, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    public function subUsers(User $user)
    {
        $subUsers = $user->subUsers()->with(['creator', 'roles'])->get();
        return response()->json($subUsers);
    }

    public function roles(User $user)
    {
        $userRoles = $user->roles->pluck('id')->toArray();
        $allRoles = Role::all();
        
        return response()->json([
            'user' => $user,
            'userRoles' => $userRoles,
            'allRoles' => $allRoles
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $oldRoles = $user->roles->pluck('name')->sort()->values()->implode(', ');
        $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->roles ?? [])->get();
        $user->syncRoles($roles);
        $newRoles = $user->fresh()->roles->pluck('name')->sort()->values()->implode(', ');

        ActivityLogger::log('updated', $user,
            ['roles' => $oldRoles],
            ['roles' => $newRoles]
        );

        return response()->json(['success' => true, 'message' => 'User roles updated successfully.']);
    }

    public function permissions(User $user)
    {
        // Direct permissions only (not via roles) for the "additional" panel
        $directPermissions = $user->getDirectPermissions()->pluck('id')->toArray();

        // All permissions grouped by group field
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()
            ->groupBy(fn($p) => $p->group ?: 'Other');

        return response()->json([
            'user'            => $user,
            'directPerms'     => $directPermissions,
            'allPermissions'  => $allPermissions,
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $oldPermissions = $user->getDirectPermissions()->pluck('name')->sort()->values()->implode(', ');
        $user->syncPermissions($request->permissions ?? []);
        $newPermissions = $user->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->implode(', ');

        ActivityLogger::log('updated', $user,
            ['direct_permissions' => $oldPermissions],
            ['direct_permissions' => $newPermissions]
        );

        return response()->json(['success' => true, 'message' => 'User permissions updated successfully.']);
    }

    private function validateUser(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($ignoreId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Password is required for new users, optional for updates
        if (!$ignoreId) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
