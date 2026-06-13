<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PageBuilderController extends Controller
{
    // ── List view ──────────────────────────────────────────────────────────
    public function index()
    {
        return view('panel.master.page-builder');
    }

    // ── DataTables server-side data endpoint ───────────────────────────────
    public function data(Request $request)
    {
        $query = Page::where('user_id', Auth::id())
            ->select(['id', 'page_name', 'created_at', 'updated_at']);

        return DataTables::of($query)
            ->addIndexColumn()                          // adds DT_RowIndex
            ->editColumn('created_at', fn($row) => $row->created_at->format('d M Y, h:i A'))
            ->editColumn('updated_at', fn($row) => $row->updated_at->format('d M Y, h:i A'))
            ->rawColumns([])                            // no raw HTML columns needed
            ->make(true);
    }

    // ── Fields JSON (off-canvas) ───────────────────────────────────────────
    public function fields(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        return response()->json([
            'page'   => ['id' => $page->id, 'page_name' => $page->page_name],
            'fields' => $page->fields->map(fn($f) => [
                'id'         => $f->id,
                'field_name' => $f->field_name,
                'field_type' => $f->field_type,
            ])->values(),
        ]);
    }

    // ── Create form ────────────────────────────────────────────────────────
    public function create()
    {
        return view('panel.master.page-builder-form', ['page' => null]);
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'page_name' => [
                'required',
                'string',
                'max:255',
                'unique:pages,page_name,NULL,id,user_id,' . Auth::id(),
            ],
        ], [
            'page_name.unique' => 'A page with this name already exists.',
        ]);

        Page::create([
            'user_id'   => Auth::id(),
            'page_name' => $request->page_name,
        ]);

        return redirect()->route('master.page-builder')
            ->with('success', 'Page created successfully.');
    }

    // ── Edit form ──────────────────────────────────────────────────────────
    public function edit(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        return view('panel.master.page-builder-form', compact('page'));
    }

    // ── Update ─────────────────────────────────────────────────────────────
    public function update(Request $request, Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $request->validate([
            'page_name' => [
                'required',
                'string',
                'max:255',
                'unique:pages,page_name,' . $page->id . ',id,user_id,' . Auth::id(),
            ],
        ], [
            'page_name.unique' => 'A page with this name already exists.',
        ]);

        $page->update(['page_name' => $request->page_name]);

        return redirect()->route('master.page-builder')
            ->with('success', 'Page updated successfully.');
    }

    // ── Single destroy ─────────────────────────────────────────────────────
    public function destroy(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        if ($page->is_generated) {
            $this->cleanupGenerated($page->page_name);
        }

        $page->delete();

        return redirect()->route('master.page-builder')
            ->with('success', 'Page deleted successfully.');
    }

    // ── Bulk destroy (called via fetch from DataTable) ─────────────────────
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:pages,id',
        ]);

        $pages = Page::whereIn('id', $request->ids)
            ->where('user_id', Auth::id())
            ->get();

        foreach ($pages as $page) {
            if ($page->is_generated) {
                $this->cleanupGenerated($page->page_name);
            }
            $page->delete();
        }

        return response()->json([
            'message' => 'Deleted ' . $pages->count() . ' page(s) successfully.',
            'deleted' => $pages->count(),
        ]);
    }

    // ── Internal helpers ────────────────────────────────────────────────────
    private function cleanupGenerated(string $pageName): void
    {
        $modelName  = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($pageName));
        $routeSlug  = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::plural($pageName));
        $viewFolder = resource_path("views/generated/{$routeSlug}");

        $this->deleteFileIfExists(app_path("Models/Generated/{$modelName}.php"));
        $this->deleteFileIfExists(app_path("Http/Controllers/Generated/{$modelName}Controller.php"));
        $this->deleteFileIfExists(app_path("Exports/Generated/{$modelName}Export.php"));

        if (is_dir($viewFolder)) {
            array_map('unlink', glob("{$viewFolder}/*.blade.php"));
            @rmdir($viewFolder);
        }

        $tableName = 'gen_' . \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($pageName));
        foreach (glob(database_path("migrations/*_{$tableName}_table.php")) as $file) {
            @unlink($file);
        }

        // Remove routes from generated.php (where they are actually stored)
        $generatedRoutesFile = base_path('routes/generated.php');
        if (file_exists($generatedRoutesFile)) {
            $content = file_get_contents($generatedRoutesFile);

            // Remove the use statement
            $content = preg_replace(
                "/^use App\\\\Http\\\\Controllers\\\\Generated\\\\{$modelName}Controller;\r?\n/m",
                '',
                $content
            );

            // Remove the export route
            $content = preg_replace(
                "/^[ \t]*Route::get\('{$routeSlug}\/export'[^\n]+\r?\n/m",
                '',
                $content
            );

            // Remove the export download route
            $content = preg_replace(
                "/^[ \t]*Route::get\('{$routeSlug}\/export\/\{exportLog\}\/download'[^\n]+\r?\n/m",
                '',
                $content
            );

            // Remove the resource route
            $content = preg_replace(
                "/^[ \t]*Route::resource\('{$routeSlug}'[^\n]+\r?\n/m",
                '',
                $content
            );

            // Clean up any extra blank lines (max 2 consecutive)
            $content = preg_replace("/\n{3,}/", "\n\n", $content);

            file_put_contents($generatedRoutesFile, $content);
        }
    }

    private function deleteFileIfExists(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
