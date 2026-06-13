<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class CheckPermission
{
    private const ALWAYS_ALLOW = [
        'logout',
        'master',
        'master.tab',
        'master.page-builder.get-columns',
        'settings',
        'settings.update',
        'settings.permission-groups.index',
        'settings.users.sub-users',
        'settings.users.document-access',
        'settings.users.document-access.update',
        // Document AI — all gated by auth only, no granular permission checks
        'document-ai.playground',
        'document-ai.predict',
        'document-ai.save-classification',
        'document-ai.settings',
        'document-ai.training.store',
        'document-ai.training.update',
        'document-ai.training.delete',
        'document-ai.type.toggle',
        'document-ai.reasoning',
        'document-ai.logs',
        'document-ai.logs.data',
        'document-ai.analytics',
        'document-ai.analytics.data',
        'document-ai.dept-rules',
        'document-ai.dept-rules.data',
        'document-ai.dept-rules.store',
        'document-ai.dept-rules.update',
        'document-ai.dept-rules.delete',
        // Import AJAX sub-routes — gated by import.view at page level
        'master.import.upload',
        'master.import.tables',
        'master.import.table-columns',
        'master.import.start',
        'master.import.status',
        'master.import.errors',
        'master.import.templates',
        'master.import.templates.delete',
        'master.import.api-connections.test',
        'master.import.preview',
    ];

    private const STRIP_PREFIXES = ['master.', 'settings.'];

    private const SUB_RESOURCES = [
        'roles' => 'roles.manage',
        'permissions' => 'permissions.manage',
        'fields' => 'fields.manage',
        'shares' => 'fields.manage',
    ];


    private const ACTION_MAP = [
        'index'        => 'view',
        'data'         => 'view',
        'show'         => 'view',
        'create'       => 'create',
        'store'        => 'create',
        'edit'         => 'edit',
        'update'       => 'edit',
        'destroy'      => 'delete',
        'bulk-destroy' => 'delete',
        'default'      => 'set-default',
        'current'      => 'set-current',
        'generate'     => 'generate',
        'info'         => 'update',
        'password'     => 'password.change',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }
        $routeName = $request->route()?->getName();
        if (!$routeName || in_array($routeName, self::ALWAYS_ALLOW, true)) {
            return $next($request);
        }
        $permission = $this->derive($routeName);
        if ($permission === null) {
            return $next($request);
        }
        if (!$user->can($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            abort(403, 'You do not have permission to access this page.');
        }
        return $next($request);
    }

    private function derive(string $routeName): ?string
    {
        $slug = $routeName;
        foreach (self::STRIP_PREFIXES as $prefix) {
            if (str_starts_with($slug, $prefix)) {
                $slug = substr($slug, strlen($prefix));
                break;
            }
        }
        $parts = explode('.', $slug);
        if (count($parts) === 1) {
            return $parts[0] . '.view';
        }
        foreach (self::SUB_RESOURCES as $subSegment => $subAction) {
            $pos = array_search($subSegment, $parts, true);
            if ($pos !== false && $pos > 0) {
                $parentParts = array_slice($parts, 0, $pos);
                $parentResource = implode('-', $parentParts);
                return "{$parentResource}.{$subAction}";
            }
        }
        $action = array_pop($parts);
        $mapped = self::ACTION_MAP[$action] ?? null;
        if ($mapped === null) {
            return null;
        }
        $resource = implode('-', $parts);
        return "{$resource}.{$mapped}";
    }
}
