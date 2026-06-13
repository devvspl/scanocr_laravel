<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageShare;
use Illuminate\Http\JsonResponse;

class PageShareController extends Controller
{
    /**
     * Public API endpoint — returns form structure for a valid share token.
     * No authentication required.
     *
     * GET /api/forms/{token}
     */
    public function show(string $token): JsonResponse
    {
        $share = PageShare::with('page.fields', 'page.user:id,name')
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$share || !$share->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired share link.',
            ], 404);
        }

        $share->recordAccess();

        $page = $share->page;

        return response()->json([
            'success' => true,
            'data'    => [
                'form' => [
                    'name'       => $page->page_name,
                    'shared_by'  => $page->user->name ?? null,
                    'shared_at'  => $share->created_at->toIso8601String(),
                    'expires_at' => $share->expires_at?->toIso8601String(),
                ],
                'fields' => $page->fields->map(fn($f) => [
                    'name'        => $f->field_name,
                    'type'        => $f->field_type,
                    'label'       => $f->label ?? $f->field_name,
                    'placeholder' => $f->placeholder,
                    'default'     => $f->default_value,
                    'required'    => $f->is_required,
                    'unique'      => $f->is_unique,
                    'nullable'    => $f->is_nullable,
                    'col_span'    => $f->col_span,
                    'description' => $f->description,
                    'options'     => $f->options,
                    'repeater_columns' => $f->repeater_columns,
                ])->values(),
            ],
        ]);
    }
}
