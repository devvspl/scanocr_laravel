<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $groups = AccountGroup::orderBy('name')->get();
        return view('panel.accounts.chart-of-accounts', compact('groups'));
    }

    public function data(Request $request)
    {
        $query = Account::with('group', 'creator');

        if ($request->filled('group')) {
            $query->where('account_group_id', $request->group);
        }
        if ($request->filled('nature')) {
            $query->whereHas('group', fn($q) => $q->where('nature', $request->nature));
        }

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                                      ->orWhere('code', 'like', "%{$search}%"));
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['code', 'name', 'group_name', 'opening_balance', 'created_by_name', 'is_active'];
        $colIdx = (int) ($order[0]['column'] ?? 0);
        $col    = $cols[$colIdx] ?? 'code';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($col === 'group_name') {
            $query->join('account_groups', 'accounts.account_group_id', '=', 'account_groups.id')
                  ->orderBy('account_groups.name', $dir)
                  ->select('accounts.*');
        } else {
            $sortable = ['code', 'name', 'opening_balance', 'is_active'];
            $query->orderBy(in_array($col, $sortable) ? $col : 'code', $dir);
        }

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        $data = $rows->map(fn($a) => [
            'id'               => $a->id,
            'code'             => $a->code,
            'name'             => $a->name,
            'group_name'       => $a->group?->name ?? '—',
            'opening_balance'  => number_format((float) $a->opening_balance, 2),
            'balance_type'     => $a->balance_type,
            'is_active'        => $a->is_active,
            'account_group_id' => $a->account_group_id,
            'description'      => $a->description,
            'created_by_name'  => $a->creator?->name ?? '—',
        ]);

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:accounts,code'],
            'name'             => ['required', 'string', 'max:255'],
            'account_group_id' => ['required', 'exists:account_groups,id'],
            'opening_balance'  => ['nullable', 'numeric'],
            'balance_type'     => ['required', 'in:debit,credit'],
            'description'      => ['nullable', 'string', 'max:500'],
            'is_active'        => ['nullable', 'boolean'],
        ]);
        $data['is_active']       = $request->boolean('is_active', true);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['created_by']      = auth()->id();

        $account = Account::create($data);

        ActivityLogger::log('created', $account, null, $account->getAttributes());

        return response()->json(['success' => true, 'message' => 'Account created successfully.']);
    }

    public function show(Account $account)
    {
        return response()->json([
            'id'               => $account->id,
            'code'             => $account->code,
            'name'             => $account->name,
            'account_group_id' => $account->account_group_id,
            'opening_balance'  => $account->opening_balance,
            'balance_type'     => $account->balance_type,
            'description'      => $account->description,
            'is_active'        => $account->is_active,
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:accounts,code,' . $account->id],
            'name'             => ['required', 'string', 'max:255'],
            'account_group_id' => ['required', 'exists:account_groups,id'],
            'opening_balance'  => ['nullable', 'numeric'],
            'balance_type'     => ['required', 'in:debit,credit'],
            'description'      => ['nullable', 'string', 'max:500'],
            'is_active'        => ['nullable', 'boolean'],
        ]);
        $data['is_active']       = $request->boolean('is_active', true);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        $old = $account->getAttributes();
        $account->update($data);

        ActivityLogger::log('updated', $account, $old, $account->getAttributes());

        return response()->json(['success' => true, 'message' => 'Account updated successfully.']);
    }

    public function destroy(Account $account)
    {
        $snapshot = $account->getAttributes();

        $account->delete();

        ActivityLogger::log('deleted', $account, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Account deleted.']);
    }
}
