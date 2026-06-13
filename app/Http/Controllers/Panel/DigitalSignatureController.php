<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ApprovalLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalSignatureController extends Controller
{
    /**
     * Show the public signing page (token-based, no auth required).
     */
    public function showSigningPage(string $token)
    {
        $log = ApprovalLog::where('token', $token)
            ->where('action', 'pending')
            ->with('user')
            ->first();

        if (!$log) {
            return view('public.signature-expired');
        }

        // Check if this level requires signature
        $approvalSetting = \App\Models\ApprovalSetting::where('document_type', $log->document_type)->first();
        $requireSignature = false;
        if ($approvalSetting) {
            $levelConfig = $approvalSetting->getLevel($log->level);
            $requireSignature = $levelConfig['require_signature'] ?? false;
        }

        // Check if user has a saved signature
        $userSignatureUrl = null;
        if ($log->user && $log->user->signature_path) {
            $userSignatureUrl = Storage::disk('public')->url($log->user->signature_path);
        }

        return view('public.signature-sign', [
            'log'              => $log,
            'token'            => $token,
            'user'             => $log->user,
            'requireSignature' => $requireSignature,
            'userSignatureUrl' => $userSignatureUrl,
        ]);
    }

    /**
     * Process the signature submission from the public page.
     */
    public function processSignature(Request $request, string $token)
    {
        $log = ApprovalLog::where('token', $token)
            ->where('action', 'pending')
            ->first();

        if (!$log) {
            return response()->json(['success' => false, 'message' => 'This link has expired or already been used.'], 422);
        }

        $request->validate([
            'signature'        => ['required', 'string'], // base64 data URL
            'remarks'          => ['nullable', 'string', 'max:500'],
            'action'           => ['required', 'in:approve,reject'],
            'save_for_future'  => ['nullable', 'boolean'],
        ]);

        // Save signature image from base64
        $signaturePath = null;
        if ($request->action === 'approve') {
            $signatureData = $request->signature;
            if (Str::startsWith($signatureData, 'data:image')) {
                $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
            }
            $imageData = base64_decode($signatureData);
            $filename  = 'signatures/' . $log->document_type . '/' . $log->document_id . '/' . Str::random(32) . '.png';
            Storage::disk('public')->put($filename, $imageData);
            $signaturePath = $filename;

            // Save as user's default signature for future use
            if ($request->boolean('save_for_future', false) && $log->user) {
                $userFile = 'signatures/users/' . $log->user_id . '_' . Str::random(16) . '.png';
                if ($log->user->signature_path) {
                    Storage::disk('public')->delete($log->user->signature_path);
                }
                Storage::disk('public')->put($userFile, $imageData);
                $log->user->update(['signature_path' => $userFile]);
            }
        }

        // Update the approval log
        $log->update([
            'action'         => $request->action === 'approve' ? 'approved' : 'rejected',
            'remarks'        => $request->remarks,
            'signature_path' => $signaturePath,
            'ip_address'     => $request->ip(),
            'user_agent'     => Str::limit($request->userAgent(), 500),
            'signed_at'      => now(),
            'acted_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->action === 'approve'
                ? 'Document signed and approved successfully.'
                : 'Document rejected.',
        ]);
    }

    /**
     * Upload user's default signature (authenticated).
     */
    public function uploadUserSignature(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'string'], // base64 data URL
        ]);

        $user = auth()->user();
        $signatureData = $request->signature;

        if (Str::startsWith($signatureData, 'data:image')) {
            $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
        }

        $imageData = base64_decode($signatureData);
        $filename  = 'signatures/users/' . $user->id . '_' . Str::random(16) . '.png';

        // Delete old signature if exists
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        Storage::disk('public')->put($filename, $imageData);
        $user->update(['signature_path' => $filename]);

        return response()->json([
            'success' => true,
            'message' => 'Signature uploaded successfully.',
            'path'    => Storage::disk('public')->url($filename),
        ]);
    }

    /**
     * Delete user's signature (authenticated).
     */
    public function deleteUserSignature()
    {
        $user = auth()->user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
            $user->update(['signature_path' => null]);
        }

        return response()->json(['success' => true, 'message' => 'Signature removed.']);
    }

    /**
     * Show the public signature upload page (token from email).
     */
    public function showUploadPage(string $token)
    {
        $userId = \Illuminate\Support\Facades\Cache::get("signature_upload_token:{$token}");

        if (!$userId) {
            return view('public.signature-expired');
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return view('public.signature-expired');
        }

        $existingUrl = $user->signature_path ? Storage::disk('public')->url($user->signature_path) : null;

        return view('public.signature-upload', [
            'user'        => $user,
            'token'       => $token,
            'existingUrl' => $existingUrl,
        ]);
    }

    /**
     * Process signature upload from the public page (token from email).
     */
    public function processUpload(Request $request, string $token)
    {
        $userId = \Illuminate\Support\Facades\Cache::get("signature_upload_token:{$token}");

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'This link has expired.'], 422);
        }

        $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $user = \App\Models\User::findOrFail($userId);
        $signatureData = $request->signature;

        if (Str::startsWith($signatureData, 'data:image')) {
            $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
        }

        $imageData = base64_decode($signatureData);
        $filename  = 'signatures/users/' . $user->id . '_' . Str::random(16) . '.png';

        // Delete old signature
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        Storage::disk('public')->put($filename, $imageData);
        $user->update(['signature_path' => $filename]);

        // Invalidate the token after use
        \Illuminate\Support\Facades\Cache::forget("signature_upload_token:{$token}");

        return response()->json([
            'success' => true,
            'message' => 'Signature saved successfully. You can close this page.',
        ]);
    }

    /**
     * Get user's current signature (authenticated).
     */
    public function getUserSignature()
    {
        $user = auth()->user();

        return response()->json([
            'success'   => true,
            'has_signature' => !empty($user->signature_path),
            'url'       => $user->signature_path ? Storage::disk('public')->url($user->signature_path) : null,
        ]);
    }
}
