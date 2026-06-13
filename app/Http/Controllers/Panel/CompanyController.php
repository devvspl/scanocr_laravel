<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('creator')->orderByDesc('is_default')->orderBy('name')->get();
        return view('panel.settings.company', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCompany($request);
        $data['created_by'] = auth()->id();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        // First company is always default
        if (Company::count() === 0) {
            $data['is_default'] = true;
        }

        $company = Company::create($data);
        ActivityLogger::log('created', $company, null, $company->getAttributes());

        // Seed default numbering settings for this company
        foreach (\App\Models\NumberingSetting::defaults() as $default) {
            \App\Models\NumberingSetting::firstOrCreate(
                ['company_id' => $company->id, 'document_type' => $default['document_type']],
                [
                    'prefix'          => $default['prefix'],
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
        }

        return response()->json(['success' => true, 'message' => 'Company created successfully.', 'data' => ['id' => $company->id, 'name' => $company->name]]);
    }

    public function show(Company $company)
    {
        return response()->json($company);
    }

    public function update(Request $request, Company $company)
    {
        $data = $this->validateCompany($request, $company->id);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $old = $company->getAttributes();
        $company->update($data);
        ActivityLogger::log('updated', $company, $old, $company->getAttributes());

        return response()->json(['success' => true, 'message' => 'Company updated successfully.']);
    }

    public function destroy(Company $company)
    {
        if ($company->is_default) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the default company. Set another company as default first.'], 422);
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $snapshot = $company->getAttributes();
        $company->delete();
        ActivityLogger::log('deleted', $company, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Company deleted.']);
    }

    public function setDefault(Company $company)
    {
        // Remove default from all
        Company::where('is_default', true)->update(['is_default' => false]);
        $company->update(['is_default' => true]);

        ActivityLogger::log('updated', $company, ['is_default' => false], ['is_default' => true]);

        return response()->json(['success' => true, 'message' => "{$company->name} is now the default company."]);
    }

    private function validateCompany(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            // Identity
            'name'                   => ['required', 'string', 'max:255'],
            'legal_name'             => ['nullable', 'string', 'max:255'],
            'display_name'           => ['nullable', 'string', 'max:255'],
            'code'                   => ['nullable', 'string', 'max:20', 'unique:companies,code' . ($ignoreId ? ",{$ignoreId}" : '')],
            'type'                   => ['required', 'in:private_limited,public_limited,llp,partnership,proprietorship,trust,ngo,other'],
            'industry'               => ['nullable', 'string', 'max:100'],
            'website'                => ['nullable', 'url', 'max:255'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:20'],
            'mobile'                 => ['nullable', 'string', 'max:20'],
            'fax'                    => ['nullable', 'string', 'max:20'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'logo'                   => ['nullable', 'image', 'max:2048'],
            // Address
            'address_line1'          => ['nullable', 'string', 'max:255'],
            'address_line2'          => ['nullable', 'string', 'max:255'],
            'city'                   => ['nullable', 'string', 'max:100'],
            'state'                  => ['nullable', 'string', 'max:100'],
            'country'                => ['nullable', 'string', 'max:100'],
            'pincode'                => ['nullable', 'string', 'max:20'],
            // Tax
            'gstin'                  => ['nullable', 'string', 'max:20'],
            'pan'                    => ['nullable', 'string', 'max:20'],
            'tan'                    => ['nullable', 'string', 'max:20'],
            'cin'                    => ['nullable', 'string', 'max:30'],
            'msme_number'            => ['nullable', 'string', 'max:30'],
            'gst_registration_type'  => ['nullable', 'in:regular,composition,unregistered,sez,overseas'],
            'gst_registration_date'  => ['nullable', 'date'],
            // Bank
            'bank_name'              => ['nullable', 'string', 'max:255'],
            'bank_branch'            => ['nullable', 'string', 'max:255'],
            'bank_account_number'    => ['nullable', 'string', 'max:30'],
            'bank_ifsc'              => ['nullable', 'string', 'max:15'],
            'bank_swift'             => ['nullable', 'string', 'max:15'],
            'bank_account_type'      => ['nullable', 'string', 'max:30'],
            // Financial
            'fy_start_month'         => ['nullable', 'in:01,02,03,04,05,06,07,08,09,10,11,12'],
            'currency_code'          => ['nullable', 'string', 'max:10'],
            'currency_symbol'        => ['nullable', 'string', 'max:5'],
            'date_format'            => ['nullable', 'in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD'],
            'timezone'               => ['nullable', 'string', 'max:100'],
            'is_active'              => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
