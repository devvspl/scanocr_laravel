<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;

class GitDeployController extends Controller
{
    public function index()
    {
        $this->guardSuperAdmin();
        return view('panel.git-deploy');
    }

    /**
     * GET /git-deploy/status (AJAX)
     */
    public function status()
    {
        $this->guardSuperAdmin();
        $branch = $this->run('git branch --show-current');
        $status = $this->run('git status --short');
        $log    = $this->run('git log --oneline -10');
        $remote = $this->run('git remote -v');

        return response()->json([
            'success' => true,
            'branch'  => trim($branch['output']),
            'status'  => $status['output'],
            'log'     => $log['output'],
            'remote'  => $remote['output'],
            'has_changes' => !empty(trim($status['output'])),
        ]);
    }

    /**
     * POST /git-deploy/pull (AJAX)
     * Supports strategy: merge (default), rebase, force
     */
    public function pull(Request $request)
    {
        $this->guardSuperAdmin();

        $branch   = trim($this->run('git branch --show-current')['output']) ?: env('GIT_BRANCH', 'main');
        $strategy = $request->input('strategy', 'merge');

        switch ($strategy) {
            case 'rebase':
                $result = $this->runAuth("git pull --rebase origin {$branch}");
                break;

            case 'force':
                // Fetch latest then hard reset — discards all local commits
                $fetch  = $this->runAuth("git fetch origin {$branch}");
                if ($fetch['exitCode'] !== 0) {
                    return response()->json([
                        'success' => false,
                        'output'  => $fetch['output'],
                        'error'   => $fetch['error'],
                    ]);
                }
                $result = $this->run("git reset --hard origin/{$branch}");
                $result['output'] = $fetch['output'] . "\n" . $result['output'];
                break;

            default: // merge
                $result = $this->runAuth("git pull origin {$branch}");
                break;
        }

        return response()->json([
            'success' => $result['exitCode'] === 0,
            'output'  => $result['output'],
            'error'   => $result['error'],
        ]);
    }

    /**
     * POST /git-deploy/commit (AJAX)
     */
    public function commit(Request $request)
    {
        $this->guardSuperAdmin();
        $request->validate(['message' => 'required|string|max:500']);

        $message = $request->input('message');

        // Stage all changes
        $add = $this->run('git add -A');
        if ($add['exitCode'] !== 0) {
            return response()->json(['success' => false, 'output' => $add['output'], 'error' => $add['error']]);
        }

        // Commit
        $commit = $this->run("git commit -m \"{$message}\"");

        return response()->json([
            'success' => $commit['exitCode'] === 0,
            'output'  => $commit['output'],
            'error'   => $commit['error'],
        ]);
    }

    /**
     * POST /git-deploy/push (AJAX)
     */
    public function push()
    {
        $this->guardSuperAdmin();
        $branch = env('GIT_BRANCH', 'main');
        $result = $this->runAuth("git push origin {$branch}");

        return response()->json([
            'success' => $result['exitCode'] === 0,
            'output'  => $result['output'],
            'error'   => $result['error'],
        ]);
    }

    /**
     * POST /git-deploy/reset (AJAX) — discard all local changes
     */
    public function reset()
    {
        $this->guardSuperAdmin();
        $result = $this->run('git checkout -- .');

        return response()->json([
            'success' => $result['exitCode'] === 0,
            'output'  => $result['output'],
            'error'   => $result['error'],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function run(string $command): array
    {
        $result = Process::path(base_path())->timeout(60)->run($command);

        return [
            'output'   => $result->output() . $result->errorOutput(),
            'error'    => $result->errorOutput(),
            'exitCode' => $result->exitCode(),
        ];
    }

    private function runAuth(string $command): array
    {
        $username = env('GIT_USERNAME');
        $password = env('GIT_PASSWORD');
        $repoUrl  = env('GIT_REPOSITORY_URL', '');

        if ($username && $password && $repoUrl) {
            $authedUrl = str_replace('https://', "https://{$username}:{$password}@", $repoUrl);
            $command   = str_replace('origin', $authedUrl, $command);
        }

        return $this->run($command);
    }

    private function guardSuperAdmin(): void
    {
        if (!Auth::user()?->hasRole('Super Admin')) {
            abort(403, 'Access denied.');
        }
    }
}
