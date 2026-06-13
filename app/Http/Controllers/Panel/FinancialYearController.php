<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FinancialYearController extends Controller
{
    public function index()
    {
        $companies     = Company::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']);
        $selectedId    = request('company_id', $companies->first()?->id);
        $financialYears = FinancialYear::with('creator')
            ->where('company_id', $selectedId)
            ->orderByDesc('start_date')
            ->get();

        return view('panel.settings.financial-year', compact('companies', 'selectedId', 'financialYears'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'label'      => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_current'] = $request->boolean('is_current', false);
        $data['created_by'] = auth()->id();

        // Only one FY can be current per company
        if ($data['is_current']) {
            FinancialYear::where('company_id', $data['company_id'])->update(['is_current' => false]);
        }

        $fy = FinancialYear::create($data);
        ActivityLogger::log('created', $fy, null, $fy->getAttributes());

        return response()->json(['success' => true, 'message' => 'Financial year created.', 'data' => ['id' => $fy->id, 'label' => $fy->label]]);
    }

    public function show(FinancialYear $financialYear)
    {
        return response()->json([
            'id'         => $financialYear->id,
            'label'      => $financialYear->label,
            'start_date' => $financialYear->start_date->format('Y-m-d'),
            'end_date'   => $financialYear->end_date->format('Y-m-d'),
            'is_current' => $financialYear->is_current,
            'is_locked'  => $financialYear->is_locked,
            'notes'      => $financialYear->notes,
        ]);
    }

    public function update(Request $request, FinancialYear $financialYear)
    {
        $data = $request->validate([
            'label'      => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'is_locked'  => ['nullable', 'boolean'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_current'] = $request->boolean('is_current', false);
        $data['is_locked']  = $request->boolean('is_locked', false);

        if ($data['is_current']) {
            FinancialYear::where('company_id', $financialYear->company_id)
                ->where('id', '!=', $financialYear->id)
                ->update(['is_current' => false]);
        }

        $old = $financialYear->getAttributes();
        $financialYear->update($data);
        ActivityLogger::log('updated', $financialYear, $old, $financialYear->getAttributes());

        return response()->json(['success' => true, 'message' => 'Financial year updated.']);
    }

    public function destroy(FinancialYear $financialYear)
    {
        if ($financialYear->is_current) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the current financial year.'], 422);
        }
        $snapshot = $financialYear->getAttributes();
        $financialYear->delete();
        ActivityLogger::log('deleted', $financialYear, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Financial year deleted.']);
    }

    public function setCurrent(FinancialYear $financialYear)
    {
        FinancialYear::where('company_id', $financialYear->company_id)->update(['is_current' => false]);
        $financialYear->update(['is_current' => true]);

        return response()->json(['success' => true, 'message' => "{$financialYear->label} is now the active financial year."]);
    }
}
