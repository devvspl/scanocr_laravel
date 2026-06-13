<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageShareManageController extends Controller
{
    /**
     * List all shares for a page.
     */
    public function index(Page $page): JsonResponse
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $shares = $page->shares()->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'shares'  => $shares->map(fn($s) => [
                'id'               => $s->id,
                'token'            => $s->token,
                'name'             => $s->name,
                'is_active'        => $s->is_active,
                'expires_at'       => $s->expires_at?->format('Y-m-d H:i'),
                'access_count'     => $s->access_count,
                'last_accessed_at' => $s->last_accessed_at?->format('d M Y, h:i A'),
                'created_at'       => $s->created_at->format('d M Y, h:i A'),
                'url'              => url("/api/forms/{$s->token}"),
            ])->values(),
        ]);
    }

    /**
     * Create a new share link for a page.
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $share = PageShare::create([
            'page_id'    => $page->id,
            'user_id'    => Auth::id(),
            'token'      => PageShare::generateToken(),
            'name'       => $data['name'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active'  => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Share link created.',
            'share'   => [
                'id'    => $share->id,
                'token' => $share->token,
                'url'   => url("/api/forms/{$share->token}"),
            ],
        ]);
    }

    /**
     * Toggle active status of a share.
     */
    public function toggle(Page $page, PageShare $share): JsonResponse
    {
        abort_if($page->user_id !== Auth::id(), 403);
        abort_if($share->page_id !== $page->id, 404);

        $share->update(['is_active' => !$share->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => $share->is_active ? 'Share link activated.' : 'Share link deactivated.',
            'is_active' => $share->is_active,
        ]);
    }

    /**
     * Delete a share link permanently.
     */
    public function destroy(Page $page, PageShare $share): JsonResponse
    {
        abort_if($page->user_id !== Auth::id(), 403);
        abort_if($share->page_id !== $page->id, 404);

        $share->delete();

        return response()->json([
            'success' => true,
            'message' => 'Share link deleted.',
        ]);
    }
}
