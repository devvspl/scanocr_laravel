<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionGroupController extends Controller
{
    public function index()
    {
        $groups = PermissionGroup::with('creator')->orderBy('name')->get();
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('permission_groups', 'name')],
        ]);

        $group = PermissionGroup::create([
            'name'       => $request->name,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'data'    => $group,
        ]);
    }

    public function destroy(PermissionGroup $permissionGroup)
    {
        $permissionGroup->delete();
        return response()->json(['success' => true, 'message' => 'Group deleted successfully.']);
    }
}
