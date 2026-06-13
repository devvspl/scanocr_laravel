<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    public function index()
    {
        $tree   = AccountGroup::with('children.creator', 'creator')->whereNull('parent_id')->orderBy('name')->get();
        $groups = AccountGroup::orderBy('name')->get();
        $natures = \App\Models\Nature::where('is_active', true)->orderBy('name')->get();
        return view('panel.accounts.account-groups', compact('tree', 'groups', 'natures'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'nature'    => ['required', 'string', 'exists:natures,slug'],
            'parent_id' => ['nullable', 'exists:account_groups,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        $group = AccountGroup::create($data);

        ActivityLogger::log('created', $group, null, $group->getAttributes());

        return back()->with('success', 'Account group created successfully.');
    }

    public function update(Request $request, AccountGroup $accountGroup)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'nature'    => ['required', 'string', 'exists:natures,slug'],
            'parent_id' => ['nullable', 'exists:account_groups,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['parent_id'] && $this->isDescendant($accountGroup, (int) $data['parent_id'])) {
            return back()->withErrors(['parent_id' => 'Cannot set a group as its own parent.'])->withInput();
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $old = $accountGroup->getAttributes();
        $accountGroup->update($data);

        ActivityLogger::log('updated', $accountGroup, $old, $accountGroup->getAttributes());

        return back()->with('success', 'Account group updated successfully.');
    }

    public function destroy(AccountGroup $accountGroup)
    {
        if ($accountGroup->children()->exists()) {
            return back()->with('error', 'Cannot delete a group that has child groups.');
        }
        if ($accountGroup->accounts()->exists()) {
            return back()->with('error', 'Cannot delete a group that has accounts linked to it.');
        }

        $snapshot = $accountGroup->getAttributes();

        $accountGroup->delete();

        ActivityLogger::log('deleted', $accountGroup, $snapshot, null);

        return back()->with('success', 'Account group deleted.');
    }

    private function isDescendant(AccountGroup $group, int $targetId): bool
    {
        if ($group->id === $targetId) return true;
        foreach ($group->children as $child) {
            if ($this->isDescendant($child, $targetId)) return true;
        }
        return false;
    }
}
