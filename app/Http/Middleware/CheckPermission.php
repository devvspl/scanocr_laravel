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
        'settings.users.company-access',
        'settings.users.company-access.update',
        'settings.users.location-access',
        'settings.users.location-access.update',
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
        // Workflow — Super Scanner (gated by role)
        'workflow.super-scanner.index',
        'workflow.super-scanner.data',
        'workflow.super-scanner.totals',
        'workflow.super-scanner.detail',
        'workflow.super-scanner.export.excel',
        'workflow.super-scanner.export.pdf',
        // Super Scanner — company-wise scanning (gated by role + authorizeCompany)
        'workflow.super-scanner.company',
        'workflow.super-scanner.company.scans-data',
        'workflow.super-scanner.company.pending-naming',
        'workflow.super-scanner.company.pending-verify',
        'workflow.super-scanner.company.tab-counts',
        'workflow.super-scanner.company.scan',
        'workflow.super-scanner.company.verify-document',
        'workflow.super-scanner.company.support-list',
        'workflow.super-scanner.company.supporting.store',
        'workflow.super-scanner.company.final-submit',
        'workflow.super-scanner.company.support.destroy',
        'workflow.super-scanner.company.scan.destroy',
        'workflow.super-scanner.select.locations',
        'workflow.super-scanner.select.bill-approvers',
        'workflow.super-scanner.select.vendors',
        'workflow.super-scanner.select.users',
        'workflow.super-scanner.select.doc-types',
        // Workflow — Temp Scanning (gated by role, not granular permissions)
        'workflow.temp-scan.index',
        'workflow.temp-scan.store',
        'workflow.temp-scan.data',
        'workflow.temp-scan.locations',
        'workflow.temp-scan.supporting',
        'workflow.temp-scan.supporting.store',
        'workflow.temp-scan.final-submit',
        'workflow.temp-scan.replace',
        'workflow.temp-scan.destroy',
        'workflow.temp-scan.support.destroy',
        'workflow.temp-scan.bill-approvers',
        'workflow.temp-scan.doc-types',
        'workflow.temp-scan.support-list',
        'workflow.temp-scan.export.excel',
        'workflow.temp-scan.export.pdf',
        'workflow.temp-scan.export.logs',
        // Workflow — Direct Scanning (gated by role, not granular permissions)
        'workflow.direct-scan.index',
        'workflow.direct-scan.store',
        'workflow.direct-scan.data',
        'workflow.direct-scan.tab-counts',
        'workflow.direct-scan.locations',
        'workflow.direct-scan.bill-approvers',
        'workflow.direct-scan.doc-types',
        'workflow.direct-scan.companies',
        'workflow.direct-scan.financial-years',
        'workflow.direct-scan.vendors',
        'workflow.direct-scan.export.excel',
        'workflow.direct-scan.export.pdf',
        'workflow.direct-scan.export.logs',
        'workflow.direct-scan.support-list',
        'workflow.direct-scan.supporting.store',
        'workflow.direct-scan.final-submit',
        'workflow.direct-scan.resubmit',
        'workflow.direct-scan.replace',
        'workflow.direct-scan.destroy',
        'workflow.direct-scan.support.destroy',
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
