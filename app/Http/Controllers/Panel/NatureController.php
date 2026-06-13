<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Nature;
use Illuminate\Http\Request;

class NatureController extends Controller
{
    public function index()
    {
        $natures = Nature::orderBy('name')->get();
        return response()->json(['data' => $natures]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'unique:natures,slug', 'regex:/^[a-z0-9-]+$/'],
            'color'     => ['nullable', 'string', 'in:blue,green,orange,red,purple,stone'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['color'] = $data['color'] ?? 'blue';

        $nature = Nature::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Nature created successfully.',
            'data' => $nature,
        ], 201);
    }

    public function update(Request $request, Nature $nature)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'unique:natures,slug,' . $nature->id, 'regex:/^[a-z0-9-]+$/'],
            'color'     => ['nullable', 'string', 'in:blue,green,orange,red,purple,stone'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['color'] = $data['color'] ?? 'blue';

        $nature->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Nature updated successfully.',
            'data' => $nature,
        ]);
    }

    public function destroy(Nature $nature)
    {
        // Check if any account groups use this nature
        $groupCount = \App\Models\AccountGroup::where('nature', $nature->slug)->count();
        
        if ($groupCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete nature. {$groupCount} account group(s) are using it.",
            ], 422);
        }

        $nature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Nature deleted successfully.',
        ]);
    }
}
