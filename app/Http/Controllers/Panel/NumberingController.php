<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\NumberingSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class NumberingController extends Controller
{
    public function index()
    {
        $companies    = Company::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']);
        $selectedId   = request('company_id', $companies->first()?->id);
        $activeTab    = request('tab', 'numbering'); // 'numbering' or 'document_types'

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

        // Approval settings per document type
        $approvalSettings = ApprovalSetting::where('company_id', $selectedId)
            ->get()
            ->keyBy('document_type');

        // Users for approver selection
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'signature_path']);

        return view('panel.settings.numbering', compact(
            'companies', 'selectedId', 'settings', 'documentTypes', 'activeTab',
            'approvalSettings', 'users'
        ));
    }

    public function update(Request $request, NumberingSetting $numberingSetting)
    {
        $data = $request->validate([
            'prefix'           => ['nullable', 'string', 'max:20'],
            'suffix'           => ['nullable', 'string', 'max:20'],
            'next_number'      => ['required', 'integer', 'min:1'],
            'pad_length'       => ['required', 'integer', 'min:1', 'max:10'],
            'reset_frequency'  => ['required', 'in:never,yearly,monthly'],
            'include_date'     => ['nullable', 'boolean'],
            'date_format'      => ['nullable', 'string', 'max:20'],
            'separator'        => ['nullable', 'string', 'max:5'],
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

    // ── Approval Settings ─────────────────────────────────────────────────────

    public function saveApproval(Request $request)
    {
        $data = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
            'document_type'  => ['required', 'string', 'max:50'],
            'is_enabled'     => ['required', 'boolean'],
            'approval_mode'  => ['required', 'in:required,auto_approved,no_approval'],
            'levels_count'   => ['required', 'integer', 'min:1', 'max:20'],
            'levels'         => ['nullable', 'array'],
            'levels.*.name'               => ['required', 'string', 'max:100'],
            'levels.*.approver_ids'       => ['nullable', 'array'],
            'levels.*.approver_ids.*'     => ['integer', 'exists:users,id'],
            'levels.*.approval_type'      => ['required', 'in:any_one,all_must'],
            'levels.*.notify_via'         => ['required', 'in:email,sms,both'],
            'levels.*.outstanding_hours'  => ['nullable', 'integer', 'min:1'],
            'levels.*.auto_reject_days'   => ['nullable', 'integer', 'min:1'],
            'levels.*.escalation_enabled' => ['nullable', 'boolean'],
            'levels.*.escalation_hours'   => ['nullable', 'integer', 'min:1'],
            'levels.*.require_signature'  => ['nullable', 'boolean'],
        ]);

        $setting = ApprovalSetting::updateOrCreate(
            [
                'company_id'    => $data['company_id'],
                'document_type' => $data['document_type'],
            ],
            [
                'is_enabled'    => $data['is_enabled'],
                'approval_mode' => $data['approval_mode'],
                'levels_count'  => $data['levels_count'],
                'levels'        => $data['levels'] ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Approval settings saved.',
            'data'    => $setting,
        ]);
    }

    public function getApproval(Request $request)
    {
        $companyId    = $request->query('company_id');
        $documentType = $request->query('document_type');

        $setting = ApprovalSetting::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    // ── Upload signature for a specific user (admin action) ───────────────────

    public function uploadSignatureForUser(Request $request)
    {
        $request->validate([
            'user_id'   => ['required', 'exists:users,id'],
            'signature' => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->user_id);
        $signatureData = $request->signature;

        if (\Illuminate\Support\Str::startsWith($signatureData, 'data:image')) {
            $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
        }

        $imageData = base64_decode($signatureData);
        $filename  = 'signatures/users/' . $user->id . '_' . \Illuminate\Support\Str::random(16) . '.png';

        // Delete old signature if exists
        if ($user->signature_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signature_path);
        }

        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageData);
        $user->update(['signature_path' => $filename]);

        return response()->json([
            'success' => true,
            'message' => 'Signature uploaded successfully.',
            'path'    => $filename,
        ]);
    }

    // ── Send signature link to user via email ─────────────────────────────────

    public function sendSignatureLink(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user  = User::findOrFail($request->user_id);
        $token = \Illuminate\Support\Str::random(64);

        // Store token in cache for 72 hours
        \Illuminate\Support\Facades\Cache::put("signature_upload_token:{$token}", $user->id, now()->addHours(72));

        $signUrl = config('app.url') . '/signature/upload/' . $token;

        // Send email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\SignatureUploadMail($user, $signUrl));
        } catch (\Exception $e) {
            \Log::error("Failed to send signature link to {$user->email}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signing link sent to ' . $user->email,
        ]);
    }
}
