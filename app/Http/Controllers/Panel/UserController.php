<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->select(['id', 'name', 'email', 'phone', 'designation', 'department', 'employee_id', 'is_core_user', 'parent_id', 'is_active', 'created_by', 'created_at']);

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

        // Filter by core user flag
        if ($request->filled('is_core_user')) {
            $query->where('is_core_user', (int) $request->is_core_user);
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
            ->addColumn('employee_id_val', function ($user) {
                return $user->employee_id ?? '';
            })
            ->addColumn('is_core_user_val', function ($user) {
                return (int) $user->is_core_user;
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

        // Bust company access cache — role may have changed
        \App\Services\UserAccessService::forgetAll($user->id);

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
            \App\Services\UserAccessService::forgetAll($user->id);
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

        // Role change may affect Super Admin status → bust company cache
        \App\Services\UserAccessService::forgetAll($user->id);

        return response()->json(['success' => true, 'message' => 'User roles updated successfully.']);
    }

    public function permissions(User $user)
    {
        // Direct permissions only (not via roles) for the "additional" panel
        $directPermissions = $user->getDirectPermissions()->pluck('id')->toArray();

        // All permissions grouped by group field
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()
            ->groupBy(fn($p) => $p->group ?: 'Other');

        // Include role names so the front-end can conditionally show Location access
        $roleNames = $user->getRoleNames();

        return response()->json([
            'user'            => $user,
            'directPerms'     => $directPermissions,
            'allPermissions'  => $allPermissions,
            'userRoles'       => $roleNames,
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

    public function documentAccess(User $user)
    {
        $allTypes = \App\Models\DocumentType::orderBy('label')->get(['id', 'key', 'label', 'is_active']);

        $access = \App\Models\UserDocumentTypeAccess::where('user_id', $user->id)
            ->pluck('can_view', 'document_type_id');

        $hasExplicitAccess = $access->isNotEmpty();

        $types = $allTypes->map(function ($dt) use ($access, $hasExplicitAccess) {
            return [
                'id'        => $dt->id,
                'key'       => $dt->key,
                'label'     => $dt->label,
                'is_active' => $dt->is_active,
                'can_view'  => $hasExplicitAccess
                    ? (bool) ($access[$dt->id] ?? false)
                    : (bool) $dt->is_active,
            ];
        });

        return response()->json([
            'user'  => ['id' => $user->id, 'name' => $user->name],
            'types' => $types,
        ]);
    }

    public function updateDocumentAccess(Request $request, User $user)
    {
        $request->validate([
            'access'            => 'array',
            'access.*.id'       => 'required|exists:document_types,id',
            'access.*.can_view' => 'required|boolean',
        ]);

        $now = now();
        foreach ($request->access as $item) {
            \App\Models\UserDocumentTypeAccess::updateOrCreate(
                ['user_id' => $user->id, 'document_type_id' => $item['id']],
                ['can_view' => $item['can_view'], 'updated_at' => $now]
            );
        }

        ActivityLogger::log('updated', $user, [], ['document_access' => 'updated']);

        return response()->json(['success' => true, 'message' => 'Document access updated.']);
    }

    public function companyAccess(User $user)
    {
        $allCompanies = \App\Models\Company::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        $access = \App\Models\UserCompanyAccess::where('user_id', $user->id)
            ->pluck('has_access', 'company_id');

        $hasExplicit = $access->isNotEmpty();

        $companies = $allCompanies->map(function ($co) use ($access, $hasExplicit) {
            return [
                'id'         => $co->id,
                'name'       => $co->name,
                'is_default' => $co->is_default,
                'is_active'  => true,
                // default open — if no rows set, all active companies are accessible
                'has_access' => $hasExplicit
                    ? (bool) ($access[$co->id] ?? false)
                    : true,
            ];
        });

        return response()->json([
            'user'      => ['id' => $user->id, 'name' => $user->name],
            'companies' => $companies,
        ]);
    }

    public function updateCompanyAccess(Request $request, User $user)
    {
        $request->validate([
            'access'               => 'array',
            'access.*.id'          => 'required|exists:companies,id',
            'access.*.has_access'  => 'required|boolean',
        ]);

        $now = now();
        foreach ($request->access as $item) {
            \App\Models\UserCompanyAccess::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $item['id']],
                ['has_access' => $item['has_access'], 'updated_at' => $now]
            );
        }

        // Bust the cached company list for this user
        \App\Services\UserAccessService::forgetCompanyCache($user->id);

        ActivityLogger::log('updated', $user, [], ['company_access' => 'updated']);

        return response()->json(['success' => true, 'message' => 'Company access updated.']);
    }

    public function locationAccess(User $user)
    {
        // Only relevant when user has Bill Approval role
        $allLocations = \App\Models\Location::active()
            ->orderBy('location_name')
            ->get(['location_id', 'location_name', 'location_code']);

        $access      = \App\Models\UserLocationAccess::where('user_id', $user->id)
            ->pluck('has_access', 'location_id');
        $hasExplicit = $access->isNotEmpty();

        $locations = $allLocations->map(function ($loc) use ($access, $hasExplicit) {
            return [
                'id'         => $loc->location_id,
                'name'       => $loc->location_name,
                'code'       => $loc->location_code,
                'has_access' => $hasExplicit
                    ? (bool) ($access[$loc->location_id] ?? false)
                    : true, // default: all open
            ];
        });

        return response()->json([
            'user'      => ['id' => $user->id, 'name' => $user->name],
            'locations' => $locations,
        ]);
    }

    public function updateLocationAccess(Request $request, User $user)
    {
        $request->validate([
            'access'               => 'array',
            'access.*.id'          => 'required|exists:master_work_location,location_id',
            'access.*.has_access'  => 'required|boolean',
        ]);

        $now = now();
        foreach ($request->access as $item) {
            \App\Models\UserLocationAccess::updateOrCreate(
                ['user_id' => $user->id, 'location_id' => $item['id']],
                ['has_access' => $item['has_access'], 'updated_at' => $now]
            );
        }

        ActivityLogger::log('updated', $user, [], ['location_access' => 'updated']);

        return response()->json(['success' => true, 'message' => 'Location access updated.']);
    }

    /*** Sync employees from core_employee_tools → users ***/
    public function syncCoreUsers()
    {
        $employees = DB::table('core_employee_tools')->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No employee data found in core_employee_tools.',
            ], 422);
        }

        $defaultPassword = (string) env('CORE_SYNC_DEFAULT_PASSWORD', '');
        $hashedPassword  = Hash::make($defaultPassword);

        $added       = 0;
        $updated     = 0;
        $deactivated = 0;
        $skipped     = 0;
        $conflicts   = [];

        DB::beginTransaction();
        try {
            foreach ($employees as $emp) {
                $empId = trim((string) ($emp->employee_id ?? ''));
                if ($empId === '') {
                    $skipped++;
                    continue;
                }

                $empName   = trim((string) ($emp->emp_name ?? ''));
                $empEmail  = trim((string) ($emp->emp_email ?? ''));
                $empPhone  = trim((string) ($emp->emp_contact ?? ''));
                $empDesig  = trim((string) ($emp->emp_designation ?? ($emp->designation ?? '')));
                $empDept   = trim((string) ($emp->emp_department ?? ($emp->department ?? '')));
                $empStatus = strtoupper(trim((string) ($emp->emp_status ?? '')));
                $isActive  = in_array($empStatus, ['A', 'ACTIVE'], true);

                // Email must be valid + unique. Fall back to a deterministic local email.
                $email = filter_var($empEmail, FILTER_VALIDATE_EMAIL)
                    ? strtolower($empEmail)
                    : strtolower($empId) . '@core.local';

                $name = $empName !== '' ? $empName : $empId;

                $existing = User::where('employee_id', $empId)->first();

                if (! $existing) {
                    // Avoid violating the email unique index
                    $emailOwner = User::where('email', $email)->first();
                    if ($emailOwner) {
                        $conflicts[] = "Email {$email} already used by user #{$emailOwner->id}; skipped employee {$empId}.";
                        $skipped++;
                        continue;
                    }

                    User::create([
                        'name'         => $name,
                        'email'        => $email,
                        'password'     => $hashedPassword,
                        'phone'        => $empPhone ?: null,
                        'designation'  => $empDesig ?: null,
                        'department'   => $empDept  ?: null,
                        'employee_id'  => $empId,
                        'is_core_user' => true,
                        'is_active'    => $isActive,
                        'created_by'   => auth()->id(),
                    ]);
                    $added++;
                    if (! $isActive) {
                        $deactivated++;
                    }
                    continue;
                }

                $changes = [];
                if ($name !== '' && $existing->name !== $name) {
                    $changes['name'] = $name;
                }
                if ($empEmail !== '' && $existing->email !== $email) {
                    $emailTaken = User::where('email', $email)->where('id', '!=', $existing->id)->exists();
                    if ($emailTaken) {
                        $conflicts[] = "Email {$email} taken by another user; left employee {$empId} unchanged.";
                    } else {
                        $changes['email'] = $email;
                    }
                }
                if ($empPhone !== '' && $existing->phone !== $empPhone) {
                    $changes['phone'] = $empPhone;
                }
                if ($empDesig !== '' && $existing->designation !== $empDesig) {
                    $changes['designation'] = $empDesig;
                }
                if ($empDept !== '' && $existing->department !== $empDept) {
                    $changes['department'] = $empDept;
                }
                if (! $existing->is_core_user) {
                    $changes['is_core_user'] = true;
                }
                if ((bool) $existing->is_active !== $isActive) {
                    $changes['is_active'] = $isActive;
                    if (! $isActive) {
                        $deactivated++;
                    }
                }

                if (! empty($changes)) {
                    $existing->update($changes);
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Sync complete. Added: {$added}, Updated: {$updated}, Deactivated: {$deactivated}, Skipped: {$skipped}.",
            'stats'   => [
                'added'       => $added,
                'updated'     => $updated,
                'deactivated' => $deactivated,
                'skipped'     => $skipped,
                'conflicts'   => $conflicts,
            ],
        ]);
    }

    private function validateUser(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($ignoreId)],
            'phone'       => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department'  => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:100'],
            'is_core_user'=> ['nullable', 'boolean'],
            'parent_id'   => ['nullable', 'exists:users,id'],
            'is_active'   => ['nullable', 'boolean'],
        ];

        // Password is required for new users, optional for updates
        if (!$ignoreId) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $data = $request->validate($rules);
        $data['is_active']    = $request->boolean('is_active', true);
        $data['is_core_user'] = $request->boolean('is_core_user', false);

        return $data;
    }
}
