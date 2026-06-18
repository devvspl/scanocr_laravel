@extends('layouts.app')
@section('title', 'Core API Sync')
@section('page-title', 'Core API Sync')

@section('content')
<div id="core-api-page">

    @include('panel.settings._nav')

    {{-- Main Card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-stone-700">Available APIs</span>
                <span id="api-count-badge" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">0 total</span>
                <button id="btn-sync-selected" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 disabled:opacity-60 text-white text-xs font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync Selected (<span id="selected-count">0</span>)
                </button>
            </div>
            <button id="btn-fetch-list" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                <svg id="fetch-icon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <svg id="fetch-spinner" class="w-4 h-4 shrink-0 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Fetch API List
            </button>
        </div>

        <div id="global-banner" class="hidden px-4 py-3 border-b border-stone-100 text-sm font-medium"></div>

        <div class="overflow-x-auto">
            <table id="core-api-table" class="w-full">
                <thead>
                    <tr>
                        <th class="td-center" style="width:36px;"><input type="checkbox" id="chk-all" class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 cursor-pointer"></th>
                        <th class="td-center" style="width:46px;">#</th>
                        <th>Endpoint</th>
                        <th>Table Name</th>
                        <th class="td-center">Last Synced At</th>
                        <th class="td-center">Status</th>
                        <th class="td-center" style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         SYNC PARAMS MODAL
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="sync-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="sync-modal-backdrop"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden" style="width:520px;max-width:calc(100vw - 2rem)">

            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-stone-800">Sync API</h3>
                    <p class="text-xs text-stone-400 mt-0.5">Endpoint: <code id="sync-modal-endpoint" class="font-mono text-red-700"></code></p>
                </div>
                <button id="sync-modal-close" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5 space-y-4">

                {{-- Query Parameters builder --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-stone-600 uppercase tracking-wide">Query Parameters</label>
                        <button id="btn-add-param" type="button"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-stone-200 bg-stone-50 hover:bg-stone-100 text-xs font-medium text-stone-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Add Parameter
                        </button>
                    </div>
                    <div id="params-list" class="space-y-2">
                        {{-- rows injected by JS --}}
                    </div>
                    <p class="text-[11px] text-stone-400 mt-2">These are sent as query-string parameters to the external API (e.g. <code class="font-mono">state_id=5</code>).</p>
                    <p id="last-params-hint" class="hidden text-[11px] text-green-600 mt-1 font-medium"></p>
                </div>

                {{-- Sync result inline --}}
                <div id="sync-modal-result" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>
            </div>

            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button id="sync-modal-cancel" type="button" class="tb-btn tb-btn-edit">Cancel</button>
                <button id="sync-modal-submit" type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Run Sync
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         VIEW DATA MODAL
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="view-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="view-modal-backdrop"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col" style="width:960px;max-width:calc(100vw - 2rem);max-height:calc(100vh - 4rem)">
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-semibold text-stone-800">Table Data: <code id="modal-table-name" class="font-mono text-red-700 text-xs ml-1"></code></h3>
                <button id="view-modal-close" class="act-btn act-edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="flex-1 overflow-hidden flex flex-col p-4 min-h-0">
                <div id="modal-error" class="hidden py-8 text-center text-sm text-red-600"></div>
                <div id="modal-dt-wrap" class="flex-1 overflow-auto">
                    <table id="modal-dt-table" class="w-full text-xs" style="display:none">
                        <thead id="modal-dt-thead"><tr></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>{{-- #core-api-page --}}
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ── Helpers ───────────────────────────────────────────────────────────────

    function showBanner(type, msg) {
        const el = document.getElementById('global-banner');
        el.className = 'px-4 py-3 border-b border-stone-100 text-sm font-medium '
            + (type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
        el.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 5000);
    }

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    }

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function spinnerHtml() {
        return `<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>`;
    }

    function statusBadge(v) {
        const map = { synced: 'bg-green-50 text-green-700', failed: 'bg-red-50 text-red-700', pending: 'bg-stone-100 text-stone-500' };
        const cls = map[v] || 'bg-stone-100 text-stone-500';
        const lbl = v ? v.charAt(0).toUpperCase() + v.slice(1) : 'Pending';
        return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${cls}">${escHtml(lbl)}</span>`;
    }

    // ── Main DataTable ────────────────────────────────────────────────────────

    const table = $('#core-api-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("settings.core-api-sync.data") }}',
            type: 'GET',
            dataSrc: function (json) {
                document.getElementById('api-count-badge').textContent = json.recordsTotal + ' total';
                return json.data;
            },
        },
        columns: [
            {
                data: 'id', orderable: false, className: 'td-center',
                render: (id, t, row) =>
                    `<input type="checkbox" class="row-chk w-3.5 h-3.5 rounded border-stone-300 text-red-700 cursor-pointer"
                            data-id="${id}" data-endpoint="${escHtml(row.api_end_point)}"
                            data-table="${escHtml(row.table_name)}"
                            data-params="${escHtml(JSON.stringify(row.parameters || {}))}">`,
            },
            {
                data: 'id', className: 'td-center text-stone-400 text-xs', orderable: false,
                render: (v, t, r, m) => m.row + 1,
            },
            {
                data: 'api_end_point',
                render: v => `<code class="text-xs bg-stone-100 px-2 py-0.5 rounded font-mono text-stone-700">${escHtml(v)}</code>`,
            },
            {
                data: 'table_name',
                render: v => `<code class="text-xs bg-stone-100 px-2 py-0.5 rounded font-mono text-stone-600">${escHtml(v)}</code>`,
            },
            {
                data: 'last_synced_at', className: 'td-center',
                render: v => `<span class="text-xs text-stone-500">${escHtml(v)}</span>`,
            },
            {
                data: 'sync_status', className: 'td-center',
                render: v => statusBadge(v),
            },
            {
                data: 'id', orderable: false, className: 'td-center',
                render: (id, t, row) => {
                    const ep         = escHtml(row.api_end_point);
                    const tn         = escHtml(row.table_name);
                    // Store params as base64 to avoid HTML attribute encoding issues
                    const paramsB64      = btoa(unescape(encodeURIComponent(JSON.stringify(row.parameters || {}))));
                    const lastParamsB64  = btoa(unescape(encodeURIComponent(JSON.stringify(row.last_used_params || {}))));
                    return `<div class="act-group justify-center">
                        <button class="act-btn act-edit btn-sync" data-id="${id}" data-endpoint="${ep}" data-table="${tn}" data-params-b64="${paramsB64}" data-last-params-b64="${lastParamsB64}" title="Sync">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                        <button class="act-btn act-edit btn-view" data-id="${id}" data-table="${tn}" title="View Data">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button class="act-btn act-edit btn-empty" data-id="${id}" data-table="${tn}" title="Empty table">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        <button class="act-btn act-delete btn-drop" data-id="${id}" data-table="${tn}" title="Drop table">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>`;
                },
            },
        ],
        order: [[1, 'asc']], pageLength: 25, pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        columnDefs: [
            { targets: 0, width: '36px' }, { targets: 1, width: '46px' },
            { targets: [4, 5], width: '140px' }, { targets: 6, width: '130px' },
        ],
        language: {
            emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No APIs found. Click <strong>Fetch API List</strong> to load from the core server.</div>',
            processing: '<div class="py-4 text-center text-xs text-stone-400">Loading…</div>',
        },
    });

    window._coreApiTable = table;

    // ── Checkbox select-all ───────────────────────────────────────────────────

    $('#chk-all').on('change', function () {
        $('#core-api-table tbody .row-chk').prop('checked', this.checked);
        updateSelectionUI();
    });
    $('#core-api-table').on('change', '.row-chk', function () {
        const total = $('#core-api-table tbody .row-chk').length;
        const chkd  = $('#core-api-table tbody .row-chk:checked').length;
        $('#chk-all').prop('indeterminate', chkd > 0 && chkd < total).prop('checked', chkd === total && total > 0);
        updateSelectionUI();
    });
    table.on('draw', function () {
        $('#chk-all').prop('checked', false).prop('indeterminate', false);
        updateSelectionUI();
    });
    function updateSelectionUI() {
        const n = $('#core-api-table tbody .row-chk:checked').length;
        document.getElementById('selected-count').textContent = n;
        document.getElementById('btn-sync-selected').classList.toggle('hidden', n === 0);
    }

    // ── Sync Selected (no params modal — runs with stored params only) ────────

    document.getElementById('btn-sync-selected').addEventListener('click', async function () {
        const rows = [];
        $('#core-api-table tbody .row-chk:checked').each(function () {
            rows.push({ endpoint: $(this).data('endpoint'), table: $(this).data('table') });
        });
        if (!rows.length || !confirm(`Sync ${rows.length} selected API(s)?`)) return;

        const btn = this, origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = spinnerHtml() + ' Syncing…';

        let totalAdded = 0, totalUpdated = 0, totalRemoved = 0, failed = 0;
        for (const r of rows) {
            try {
                const json = await post('{{ route("settings.core-api-sync.sync") }}', { api_end_point: r.endpoint, table_name: r.table });
                if (json.success) { totalAdded += json.added || 0; totalUpdated += json.updated || 0; totalRemoved += json.removed || 0; }
                else failed++;
            } catch (e) { failed++; }
        }
        table.ajax.reload(null, false);
        showBanner(failed === 0 ? 'success' : 'error',
            `Synced ${rows.length - failed}/${rows.length} — Added: ${totalAdded}, Updated: ${totalUpdated}, Removed: ${totalRemoved}` + (failed ? ` (${failed} failed)` : ''));
        btn.disabled = false; btn.innerHTML = origHtml;
    });

    // ── Fetch API List ────────────────────────────────────────────────────────

    document.getElementById('btn-fetch-list').addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        document.getElementById('fetch-icon').classList.add('hidden');
        document.getElementById('fetch-spinner').classList.remove('hidden');
        post('{{ route("settings.core-api-sync.fetch") }}', {})
            .then(json => {
                json.success ? (table.ajax.reload(null, false), showBanner('success', json.message))
                             : showBanner('error', json.message || 'Failed to fetch API list.');
            })
            .catch(() => showBanner('error', 'Network error.'))
            .finally(() => {
                btn.disabled = false;
                document.getElementById('fetch-icon').classList.remove('hidden');
                document.getElementById('fetch-spinner').classList.add('hidden');
            });
    });

    // ── Button delegation ─────────────────────────────────────────────────────

    function decodeB64Json(val) {
        try { return JSON.parse(decodeURIComponent(escape(atob(val || '')))); } catch (e) { return {}; }
    }

    $('#core-api-table').on('click', '.btn-sync', function () {
        const params = decodeB64Json($(this).data('paramsB64'));
        if (Object.keys(params).length > 0) {
            openSyncModal(this);
        } else {
            directSync(this);
        }
    });
    $('#core-api-table').on('click', '.btn-view',  function () { handleView(this); });
    $('#core-api-table').on('click', '.btn-empty', function () { handleEmpty(this); });
    $('#core-api-table').on('click', '.btn-drop',  function () { handleDrop(this); });

    // ── Direct Sync (no params needed) ───────────────────────────────────────

    function directSync(btn) {
        const $btn     = $(btn);
        const endpoint = $btn.data('endpoint');
        const tbl      = $btn.data('table') || endpoint;
        const origHtml = $btn.html();

        $btn.prop('disabled', true).html(spinnerHtml());

        post('{{ route("settings.core-api-sync.sync") }}', {
            api_end_point: endpoint,
            table_name:    tbl,
        })
        .then(json => {
            if (json.success) {
                table.ajax.reload(null, false);
                showBanner('success',
                    `[${endpoint}] Sync complete — Added: ${json.added}, Updated: ${json.updated}, Removed: ${json.removed}`);
            } else {
                showBanner('error', json.message || 'Sync failed.');
            }
        })
        .catch(() => showBanner('error', 'Network error during sync.'))
        .finally(() => $btn.prop('disabled', false).html(origHtml));
    }

    // ── Sync Modal (params required) ──────────────────────────────────────────

    let _syncEndpoint = '', _syncTable = '';

    function openSyncModal(btn) {
        _syncEndpoint = $(btn).data('endpoint');
        // If table_name is blank (not yet synced), fall back to endpoint name —
        // the controller will prefix it correctly via localTableName()
        _syncTable = $(btn).data('table') || _syncEndpoint;

        // storedParams: keys defined in core_api_list.parameters (keys known, values blank)
        // lastParams:   actual key→value used in the last successful sync (from sync logs)
        let storedParams = {}, lastParams = {};
        storedParams = decodeB64Json($(btn).data('paramsB64'));
        lastParams   = decodeB64Json($(btn).data('lastParamsB64'));

        document.getElementById('sync-modal-endpoint').textContent = _syncEndpoint;
        document.getElementById('sync-modal-result').classList.add('hidden');
        document.getElementById('sync-modal-submit').disabled = false;
        document.getElementById('sync-modal-submit').innerHTML =
            `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Run Sync`;

        const list = document.getElementById('params-list');
        list.innerHTML = '';

        // Build the final set of rows:
        // - All keys from storedParams (required keys defined on the API)
        //   pre-filled with last-used value if available
        // - Any extra keys from lastParams not in storedParams (user added manually last time)
        const allKeys = new Set([...Object.keys(storedParams), ...Object.keys(lastParams)]);

        if (allKeys.size > 0) {
            allKeys.forEach(k => {
                const v = lastParams[k] !== undefined ? lastParams[k] : (storedParams[k] ?? '');
                addParamRow(k, v, k in storedParams);
            });
        } else {
            addParamRow('', ''); // one blank row as hint
        }

        // Show last-used hint if applicable
        const hint = document.getElementById('last-params-hint');
        if (Object.keys(lastParams).length > 0) {
            hint.textContent = 'Values pre-filled from last sync.';
            hint.classList.remove('hidden');
        } else {
            hint.classList.add('hidden');
        }

        const modal = document.getElementById('sync-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function addParamRow(key = '', val = '', requiredKey = false) {
        const list = document.getElementById('params-list');
        const row  = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML = `
            <input type="text" placeholder="Key" value="${escHtml(key)}"
                   class="param-key flex-1 h-8 px-2.5 text-xs border border-stone-200 rounded-lg bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 font-mono${requiredKey ? ' bg-stone-100 text-stone-500 cursor-not-allowed' : ''}"
                   ${requiredKey ? 'readonly' : ''}>
            <input type="text" placeholder="Value" value="${escHtml(val)}"
                   class="param-val flex-1 h-8 px-2.5 text-xs border border-stone-200 rounded-lg bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 font-mono">
            <button type="button" class="btn-remove-param act-btn ${requiredKey ? 'opacity-30 cursor-not-allowed' : 'act-delete'} shrink-0" title="${requiredKey ? 'Required parameter' : 'Remove'}" ${requiredKey ? 'disabled' : ''}>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        if (!requiredKey) {
            row.querySelector('.btn-remove-param').addEventListener('click', () => row.remove());
        }
        list.appendChild(row);
    }

    document.getElementById('btn-add-param').addEventListener('click', () => addParamRow());

    function closeSyncModal() {
        const modal = document.getElementById('sync-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.getElementById('sync-modal-close').addEventListener('click', closeSyncModal);
    document.getElementById('sync-modal-cancel').addEventListener('click', closeSyncModal);
    document.getElementById('sync-modal-backdrop').addEventListener('click', closeSyncModal);

    document.getElementById('sync-modal-submit').addEventListener('click', function () {
        // Collect key/value pairs — validate required keys have values
        const extraParams = {};
        let missingRequired = false;

        document.querySelectorAll('#params-list .flex').forEach(row => {
            const keyInput = row.querySelector('.param-key');
            const valInput = row.querySelector('.param-val');
            const k = keyInput?.value?.trim();
            const v = valInput?.value?.trim();
            if (!k) return;

            // Required key (readonly) must have a value
            if (keyInput.readOnly && !v) {
                valInput.classList.add('border-red-400', 'ring-1', 'ring-red-400/30');
                missingRequired = true;
            } else {
                valInput.classList.remove('border-red-400', 'ring-1', 'ring-red-400/30');
            }
            extraParams[k] = v;
        });

        if (missingRequired) {
            const result = document.getElementById('sync-modal-result');
            result.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
            result.textContent = 'Please fill in all required parameter values.';
            result.classList.remove('hidden');
            return;
        }

        const btn = this, origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = spinnerHtml() + ' Syncing…';

        const result = document.getElementById('sync-modal-result');
        result.classList.add('hidden');

        post('{{ route("settings.core-api-sync.sync") }}', {
            api_end_point: _syncEndpoint,
            table_name:    _syncTable,
            ...extraParams,
        })
        .then(json => {
            result.classList.remove('hidden');
            if (json.success) {
                result.className = 'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700';
                result.textContent = `Sync complete — Added: ${json.added}, Updated: ${json.updated}, Removed: ${json.removed}`;
                table.ajax.reload(null, false);
                setTimeout(closeSyncModal, 1800);
            } else {
                result.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
                result.textContent = json.message || 'Sync failed.';
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        })
        .catch(() => {
            result.classList.remove('hidden');
            result.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
            result.textContent = 'Network error during sync.';
            btn.disabled = false;
            btn.innerHTML = origHtml;
        });
    });

    // ── View Data Modal ───────────────────────────────────────────────────────

    let modalDT = null, modalTable = null;

    function handleView(btn) {
        const tbl = $(btn).data('table'), origHtml = $(btn).html();
        const modal = document.getElementById('view-modal');
        const errorDiv = document.getElementById('modal-error');
        const dtTable  = document.getElementById('modal-dt-table');
        const dtThead  = document.getElementById('modal-dt-thead').querySelector('tr');

        document.getElementById('modal-table-name').textContent = tbl;
        errorDiv.classList.add('hidden');
        $(btn).prop('disabled', true).html(spinnerHtml());

        fetch(`{{ route("settings.core-api-sync.modal-data") }}?table_name=${encodeURIComponent(tbl)}&draw=0&start=0&length=1`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(json => {
            $(btn).prop('disabled', false).html(origHtml);
            if (json.error) { errorDiv.textContent = json.error; errorDiv.classList.remove('hidden'); modal.classList.remove('hidden'); modal.classList.add('flex'); return; }
            const cols = json.columns || [];
            if (!cols.length) { errorDiv.textContent = 'No columns found. Sync first.'; errorDiv.classList.remove('hidden'); modal.classList.remove('hidden'); modal.classList.add('flex'); return; }

            if (modalDT && modalTable !== tbl) { modalDT.destroy(); modalDT = null; dtThead.innerHTML = ''; $('#modal-dt-table tbody').empty(); }
            modalTable = tbl;
            dtThead.innerHTML = cols.map(c => `<th class="px-3 py-2 text-left text-[10px] font-bold text-stone-500 uppercase tracking-wide whitespace-nowrap">${escHtml(c)}</th>`).join('');

            if (modalDT) { modalDT.ajax.reload(null, false); }
            else {
                $(dtTable).show();
                modalDT = $(dtTable).DataTable({
                    serverSide: true, processing: true, destroy: true,
                    ajax: { url: '{{ route("settings.core-api-sync.modal-data") }}', type: 'GET', data: { table_name: tbl } },
                    columns: cols.map(c => ({ data: null, orderable: true, render: row => `<span class="whitespace-nowrap">${escHtml(row[c] ?? '')}</span>` })),
                    order: [[0, 'asc']], pageLength: 25, pagingType: 'simple_numbers',
                    dom: '<"top"lf>t<"bottom"ip>',
                    language: { emptyTable: '<div class="py-8 text-center text-sm text-stone-400">No rows in this table.</div>', processing: '<div class="py-4 text-center text-xs text-stone-400">Loading…</div>' },
                });
            }
            modal.classList.remove('hidden'); modal.classList.add('flex');
        })
        .catch(() => { $(btn).prop('disabled', false).html(origHtml); errorDiv.textContent = 'Network error.'; errorDiv.classList.remove('hidden'); modal.classList.remove('hidden'); modal.classList.add('flex'); });
    }

    document.getElementById('view-modal-close').addEventListener('click', () => { document.getElementById('view-modal').classList.add('hidden'); document.getElementById('view-modal').classList.remove('flex'); });
    document.getElementById('view-modal-backdrop').addEventListener('click', () => { document.getElementById('view-modal').classList.add('hidden'); document.getElementById('view-modal').classList.remove('flex'); });

    // ── Empty ─────────────────────────────────────────────────────────────────

    function handleEmpty(btn) {
        const tbl = $(btn).data('table'), origHtml = $(btn).html();
        if (!confirm(`Clear all rows from [${tbl}]? Table structure stays.`)) return;
        $(btn).prop('disabled', true).html(spinnerHtml());
        post('{{ route("settings.core-api-sync.empty") }}', { table_name: tbl })
            .then(json => { json.success ? (table.ajax.reload(null, false), showBanner('success', json.message)) : showBanner('error', json.message); })
            .catch(() => showBanner('error', 'Network error.'))
            .finally(() => $(btn).prop('disabled', false).html(origHtml));
    }

    // ── Drop ──────────────────────────────────────────────────────────────────

    function handleDrop(btn) {
        const tbl = $(btn).data('table'), origHtml = $(btn).html();
        if (!confirm(`Drop table [${tbl}] and remove the API entry permanently? Cannot be undone.`)) return;
        $(btn).prop('disabled', true).html(spinnerHtml());
        post('{{ route("settings.core-api-sync.drop") }}', { table_name: tbl })
            .then(json => {
                json.success ? (table.ajax.reload(null, false), showBanner('success', json.message))
                             : (showBanner('error', json.message), $(btn).prop('disabled', false).html(origHtml));
            })
            .catch(() => { showBanner('error', 'Network error.'); $(btn).prop('disabled', false).html(origHtml); });
    }

})();
</script>
@endpush
