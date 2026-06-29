@extends('layouts.app')

@section('title', 'Git Deploy')
@section('page-title', 'Git Deploy')

@section('content')
<div x-data="gitDeployPage()" x-init="loadStatus()">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        {{-- ═══ Left: Actions ═══ --}}
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Actions
                </h3>
            </div>
            <div class="p-4 space-y-3">

                {{-- Branch Info --}}
                <div class="flex items-center gap-2 px-3 py-2 bg-stone-50 rounded-lg border border-stone-200">
                    <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-xs text-stone-500">Branch:</span>
                    <span class="text-xs font-bold text-stone-800" x-text="branch || '...'"></span>
                    <template x-if="hasChanges">
                        <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Modified</span>
                    </template>
                    <template x-if="!hasChanges && branch">
                        <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-semibold">Clean</span>
                    </template>
                </div>

                {{-- Pull Button --}}
                <button @click="pull()" :disabled="loading" class="w-full flex items-center justify-center gap-2 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span x-text="loading === 'pull' ? 'Pulling...' : 'Git Pull'"></span>
                </button>

                {{-- Commit --}}
                <div class="border border-stone-200 rounded-lg p-3">
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1.5">Commit Message</label>
                    <input type="text" x-model="commitMsg" placeholder="Enter commit message..." class="w-full h-8 px-3 text-xs border border-stone-200 rounded-md bg-stone-50 focus:outline-none focus:border-red-700">
                    <button @click="commit()" :disabled="loading || !commitMsg.trim()" class="w-full mt-2 flex items-center justify-center gap-2 h-9 rounded-lg bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="loading === 'commit' ? 'Committing...' : 'Commit All Changes'"></span>
                    </button>
                </div>

                {{-- Push Button --}}
                <button @click="push()" :disabled="loading" class="w-full flex items-center justify-center gap-2 h-10 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m4-8l-4-4m0 0L16 8m4-4v12"/></svg>
                    <span x-text="loading === 'push' ? 'Pushing...' : 'Git Push'"></span>
                </button>

                {{-- Reset --}}
                <button @click="if(confirm('Discard ALL local changes? This cannot be undone.')) reset()" :disabled="loading" class="w-full flex items-center justify-center gap-2 h-9 rounded-lg border border-stone-300 hover:bg-stone-50 disabled:opacity-50 text-stone-600 text-xs font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Discard Changes
                </button>

                {{-- Refresh --}}
                <button @click="loadStatus()" :disabled="loading" class="w-full flex items-center justify-center gap-2 h-8 text-xs text-stone-500 hover:text-stone-700 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh Status
                </button>
            </div>
        </div>

        {{-- ═══ Middle: Git Status ═══ --}}
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-700">Changed Files</h3>
            </div>
            <div class="p-4">
                <pre class="text-[11px] font-mono text-stone-700 bg-stone-50 border border-stone-200 rounded-lg p-3 max-h-[400px] overflow-auto whitespace-pre-wrap" x-text="gitStatus || 'No changes detected'"></pre>
            </div>
        </div>

        {{-- ═══ Right: Git Log + Output ═══ --}}
        <div class="space-y-3">
            {{-- Recent Commits --}}
            <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-stone-100">
                    <h3 class="text-sm font-semibold text-stone-700">Recent Commits</h3>
                </div>
                <div class="p-4">
                    <pre class="text-[11px] font-mono text-stone-600 bg-stone-50 border border-stone-200 rounded-lg p-3 max-h-[180px] overflow-auto whitespace-pre-wrap" x-text="gitLog || 'Loading...'"></pre>
                </div>
            </div>

            {{-- Command Output --}}
            <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-stone-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-stone-700">Output</h3>
                    <button x-show="cmdOutput" @click="cmdOutput=''" class="text-[10px] text-stone-400 hover:text-stone-600">Clear</button>
                </div>
                <div class="p-4">
                    <pre class="text-[11px] font-mono bg-gray-900 text-green-400 rounded-lg p-3 max-h-[180px] overflow-auto whitespace-pre-wrap" x-text="cmdOutput || '$ Waiting for command...'"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gitDeployPage() {
    return {
        loading: false,
        branch: '',
        hasChanges: false,
        gitStatus: '',
        gitLog: '',
        cmdOutput: '',
        commitMsg: '',

        async loadStatus() {
            try {
                const res = await fetch('/git-deploy/status', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const json = await res.json();
                if (json.success) {
                    this.branch = json.branch;
                    this.gitStatus = json.status || 'Working tree clean';
                    this.gitLog = json.log;
                    this.hasChanges = json.has_changes;
                }
            } catch (e) { this.cmdOutput = 'Error loading status: ' + e.message; }
        },

        async pull() {
            this.loading = 'pull'; this.cmdOutput = '$ git pull origin ' + (this.branch || 'main') + '\n\nRunning...';
            try {
                const res = await fetch('/git-deploy/pull', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const json = await res.json();
                this.cmdOutput = json.output || json.error || 'Done';
                if (json.success) this.loadStatus();
            } catch (e) { this.cmdOutput = 'Error: ' + e.message; }
            this.loading = false;
        },

        async commit() {
            if (!this.commitMsg.trim()) return;
            this.loading = 'commit'; this.cmdOutput = '$ git add -A && git commit -m "' + this.commitMsg + '"\n\nRunning...';
            try {
                const res = await fetch('/git-deploy/commit', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ message: this.commitMsg }) });
                const json = await res.json();
                this.cmdOutput = json.output || json.error || 'Done';
                if (json.success) { this.commitMsg = ''; this.loadStatus(); }
            } catch (e) { this.cmdOutput = 'Error: ' + e.message; }
            this.loading = false;
        },

        async push() {
            this.loading = 'push'; this.cmdOutput = '$ git push origin ' + (this.branch || 'main') + '\n\nRunning...';
            try {
                const res = await fetch('/git-deploy/push', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const json = await res.json();
                this.cmdOutput = json.output || json.error || 'Done';
                if (json.success) this.loadStatus();
            } catch (e) { this.cmdOutput = 'Error: ' + e.message; }
            this.loading = false;
        },

        async reset() {
            this.loading = 'reset'; this.cmdOutput = '$ git checkout -- .\n\nRunning...';
            try {
                const res = await fetch('/git-deploy/reset', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const json = await res.json();
                this.cmdOutput = json.output || json.error || 'Changes discarded';
                this.loadStatus();
            } catch (e) { this.cmdOutput = 'Error: ' + e.message; }
            this.loading = false;
        },
    };
}
</script>
@endpush
