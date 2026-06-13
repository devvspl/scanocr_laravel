<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\NumberingSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class NumberingController extends Controller
{
    public function index()
    {
        $companies  = Company::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']);
        $selectedId = request('company_id', $companies->first()?->id);

        // Load document types from DB
        $documentTypes = DocumentType::where('is_active', true)->orderBy('sort_order')->get();

        // Ensure all active document types have a numbering setting for this company
        if ($selectedId) {
            foreach ($documentTypes as $dt) {
                $existing = NumberingSetting::firstOrCreate(
                    ['company_id' => $selectedId, 'document_type' => $dt->key],
                    [
                        'prefix'          => $dt->default_prefix,
                        'suffix'          => '',
                        'next_number'     => 1,
                        'pad_length'      => 4,
                        'reset_frequency' => 'yearly',
                        'include_date'    => false,
                        'date_format'     => 'YYYY-MM',
                        'separator'       => '/',
                        'created_by'      => auth()->id(),
                    ]
                );
                // Repair blank prefix/separator
                $updates = [];
                if ($existing->prefix === '' || $existing->prefix === null) $updates['prefix'] = $dt->default_prefix;
                if ($existing->separator === '' || $existing->separator === null) $updates['separator'] = '/';
                if (!empty($updates)) $existing->update($updates);
            }
        }

        $settings = NumberingSetting::where('company_id', $selectedId)
            ->orderBy('document_type')
            ->get()
            ->keyBy('document_type');

        return view('panel.settings.numbering', compact(
            'companies', 'selectedId', 'settings', 'documentTypes'
        ));
    }

    public function update(Request $request, NumberingSetting $numberingSetting)
    {
        $data = $request->validate([
            'prefix'          => ['nullable', 'string', 'max:20'],
            'suffix'          => ['nullable', 'string', 'max:20'],
            'next_number'     => ['required', 'integer', 'min:1'],
            'pad_length'      => ['required', 'integer', 'min:1', 'max:10'],
            'reset_frequency' => ['required', 'in:never,yearly,monthly'],
            'include_date'    => ['nullable', 'boolean'],
            'date_format'     => ['nullable', 'string', 'max:20'],
            'separator'       => ['nullable', 'string', 'max:5'],
        ]);

        $data['include_date'] = $request->boolean('include_date', false);
        $data['prefix']       = $data['prefix'] ?? '';
        $data['suffix']       = $data['suffix'] ?? '';
        $data['separator']    = $data['separator'] ?? '/';

        $old = $numberingSetting->getAttributes();
        $numberingSetting->update($data);
        $numberingSetting->update(['preview' => $numberingSetting->buildPreview()]);
        ActivityLogger::log('updated', $numberingSetting, $old, $numberingSetting->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Numbering updated.',
            'preview' => $numberingSetting->buildPreview(),
        ]);
    }

    public function show(NumberingSetting $numberingSetting)
    {
        return response()->json($numberingSetting);
    }
}
