@extends('panel.master')

@php $tab = 'page-builder'; @endphp

@section('master-content')
    {{-- ── Start main card ── --}}
    <div class="bg-white border border-stone-200 overflow-hidden">

        {{-- ── Toolbar ── --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <h3 class="text-sm font-semibold text-stone-800 shrink-0">Page Builder</h3>
            <div class="flex items-center gap-1.5 ml-auto">

                {{-- Bulk bar (hidden until rows selected) --}}
                <div id="bulk-bar" class="hidden items-center gap-1.5">
                    <span id="sel-count"
                        class="inline-flex items-center h-4 px-1 text-[9px] rounded bg-stone-100 text-stone-600">
                        2 selected
                    </span>
                    {{-- Fields — single only --}}
                    <a id="btn-fields" href="#" class="tb-btn tb-btn-fields" style="display:none;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                                 M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2
                                                 m-6 9h6m-6 4h4" />
                        </svg>
                        Fields
                    </a>
                    {{-- Share API — single only --}}
                    <button id="btn-share-api" class="tb-btn tb-btn-fields" style="display:none;" title="Share form via API">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        Share API
                    </button>
                    {{-- Edit — single only --}}
                    <a id="btn-edit" href="#" class="tb-btn tb-btn-edit" style="display:none;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                                 m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                    {{-- Delete --}}
                    <button id="btn-delete" class="tb-btn tb-btn-delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7
                                                 m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                    <button id="btn-clear" class="tb-btn tb-btn-clear">Clear</button>
                </div>

                {{-- Add Page --}}
                <a href="{{ route('master.page-builder.create') }}" class="tb-btn tb-btn-add">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Page
                </a>

            </div>
        </div>

        {{-- ── Table area ── --}}
        <div id="dt-wrapper" class="overflow-x-auto">
            <table id="pages-table" class="w-full">
                <thead>
                    <tr>
                        <th class="dt-center" style="width:40px;">
                            <input id="check-all" type="checkbox" class="cb-input">
                        </th>
                        <th style="width:40px;">#</th>
                        <th>Page Name</th>
                        <th>Created</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- ── Empty state ── --}}
        <div id="empty-state" class="hidden flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-stone-100 flex items-center justify-center mb-3 mx-auto">
                <svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                         a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p id="empty-msg" class="text-sm font-medium text-stone-600">No pages yet</p>
            <p id="empty-sub" class="text-xs text-stone-400 mt-1">Click "Add Page" to get started.</p>
        </div>

    </div>
    {{-- ── End main card ── --}}
    <script>
        $(function () {

            const CSRF = '{{ csrf_token() }}';
            const fieldsUrl = id => `/master/page-builder/${id}/fields`;
            const editUrl = id => `/master/page-builder/${id}/edit`;

            /* ── SVG icon strings ─────────────────────────────────── */
            const ICO = {
                fields: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:13px;height:13px;display:block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2 M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2 m-6 9h6m-6 4h4"/></svg>`,
                edit: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:13px;height:13px;display:block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5 m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`,
                trash: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:13px;height:13px;display:block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7 m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`,
            };

            /* ════════════════════════════════
               DataTable init
            ════════════════════════════════ */
            const table = $('#pages-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('master.page-builder.data') }}',
                dom: '<"top"lf>t<"bottom"ip>',
                columns: [
                    {
                        data: null, orderable: false, searchable: false,
                        className: 'td-center',
                        render: (d, t, row) =>
                            `<input type="checkbox" class="cb-input row-check"
                                            data-id="${row.id}"
                                            data-fields="${fieldsUrl(row.id)}"
                                            data-edit="${editUrl(row.id)}">`,
                    },
                    { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'td-num' },
                    { data: 'page_name', className: 'td-name' },
                    { data: 'created_at', className: 'td-date' },
                    { data: 'updated_at', className: 'td-date' },
                ],
                order: [[3, 'desc']],
                pageLength: 10,
                pagingType: 'simple_numbers',
                drawCallback: function () {
                    const info = this.api().page.info();
                    const total = info.recordsTotal;
                    if (total === 0) {
                        $('#pages-table, .dataTables_wrapper .bottom').addClass('d-none').hide();
                        $('#empty-state').removeClass('hidden').addClass('flex');
                    } else {
                        $('#pages-table, .dataTables_wrapper .bottom').show();
                        $('#empty-state').removeClass('flex').addClass('hidden');
                    }
                    $('#check-all').prop({ checked: false, indeterminate: false });
                    syncBulkBar();
                },
            });

            /* ════════════════════════════════
               Checkbox logic
            ════════════════════════════════ */
            function getChecked() { return $('#pages-table tbody .row-check:checked'); }

            function syncBulkBar() {
                const $checked = getChecked();
                const n = $checked.length;
                const total = $('#pages-table tbody .row-check').length;

                $('#check-all').prop({
                    checked: n > 0 && n === total,
                    indeterminate: n > 0 && n < total,
                });

                if (n === 0) {
                    $('#bulk-bar').removeClass('flex').addClass('hidden');
                    return;
                }

                $('#bulk-bar').removeClass('hidden').addClass('flex');
                $('#sel-count').text(`${n} selected`);

                if (n === 1) {
                    const $cb = $checked.first();
                    $('#btn-fields').attr('href', $cb.data('fields')).css('display', 'inline-flex');
                    $('#btn-edit').attr('href', $cb.data('edit')).css('display', 'inline-flex');
                    $('#btn-share-api').css('display', 'inline-flex').data('page-id', $cb.data('id'));
                } else {
                    $('#btn-fields, #btn-edit, #btn-share-api').css('display', 'none');
                }
            }

            $('#check-all').on('change', function () {
                $('#pages-table tbody .row-check').prop('checked', this.checked);
                syncBulkBar();
            });

            $(document).on('change', '.row-check', syncBulkBar);

            $('#btn-clear').on('click', function () {
                $('#pages-table tbody .row-check').prop('checked', false);
                $('#check-all').prop({ checked: false, indeterminate: false });
                syncBulkBar();
            });


            /* ════════════════════════════════
               Bulk delete
            ════════════════════════════════ */
            $('#btn-delete').on('click', async function () {
                const $checked = getChecked();
                const n = $checked.length;
                if (!n) return;
                if (!confirm(`Delete ${n === 1 ? 'this page' : `these ${n} pages`}? This cannot be undone.`)) return;
                const ids = $checked.map((i, el) => $(el).data('id')).get();
                try {
                    const res = await fetch('{{ route('master.page-builder.bulk-destroy') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids }),
                    });
                    res.ok ? table.ajax.reload(null, false) : alert('Something went wrong. Please try again.');
                } catch { alert('Network error. Please try again.'); }
            });

            /* ════════════════════════════════
               Share API Modal
            ════════════════════════════════ */
            $('#btn-share-api').on('click', async function () {
                const pageId = $(this).data('page-id');
                if (!pageId) return;
                $('#share-modal').removeClass('hidden').addClass('flex');
                $('#share-modal-body').html('<div class="py-8 text-center text-xs text-stone-400">Loading…</div>');
                try {
                    const res = await fetch(`/master/page-builder/${pageId}/shares`, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    if (!json.success) { $('#share-modal-body').html('<p class="text-xs text-red-600">Failed to load.</p>'); return; }
                    renderShares(pageId, json.shares);
                } catch { $('#share-modal-body').html('<p class="text-xs text-red-600">Network error.</p>'); }
            });

            $(document).on('click', '#share-modal-close, #share-modal-bg', () => {
                $('#share-modal').removeClass('flex').addClass('hidden');
            });

            $(document).on('click', '#btn-create-share', async function () {
                const pageId = $(this).data('page-id');
                const name = $('#share-name-input').val() || null;
                const res = await fetch(`/master/page-builder/${pageId}/shares`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ name }),
                });
                const json = await res.json();
                if (json.success) {
                    $('#share-name-input').val('');
                    // Reload shares
                    const r2 = await fetch(`/master/page-builder/${pageId}/shares`, { headers: { 'Accept': 'application/json' } });
                    const j2 = await r2.json();
                    if (j2.success) renderShares(pageId, j2.shares);
                }
            });

            $(document).on('click', '.btn-copy-share', function () {
                const url = $(this).data('url');
                navigator.clipboard.writeText(url).then(() => {
                    $(this).text('Copied!');
                    setTimeout(() => $(this).text('Copy'), 1500);
                });
            });

            $(document).on('click', '.btn-toggle-share', async function () {
                const pageId = $(this).data('page-id');
                const shareId = $(this).data('share-id');
                await fetch(`/master/page-builder/${pageId}/shares/${shareId}/toggle`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const r = await fetch(`/master/page-builder/${pageId}/shares`, { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.success) renderShares(pageId, j.shares);
            });

            $(document).on('click', '.btn-delete-share', async function () {
                if (!confirm('Delete this share link?')) return;
                const pageId = $(this).data('page-id');
                const shareId = $(this).data('share-id');
                await fetch(`/master/page-builder/${pageId}/shares/${shareId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const r = await fetch(`/master/page-builder/${pageId}/shares`, { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.success) renderShares(pageId, j.shares);
            });

            function renderShares(pageId, shares) {
                let html = `<div class="mb-3 flex items-center gap-2">
                    <input id="share-name-input" type="text" placeholder="Link name (optional)" class="flex-1 h-8 px-3 text-xs border border-stone-300 rounded-lg focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
                    <button id="btn-create-share" data-page-id="${pageId}" class="h-8 px-3 text-xs font-semibold text-white rounded-lg" style="background:#7f1d1d;">Generate Link</button>
                </div>`;

                if (shares.length === 0) {
                    html += '<p class="text-xs text-stone-400 text-center py-4">No share links yet. Generate one above.</p>';
                } else {
                    html += '<div class="space-y-2 max-h-64 overflow-y-auto">';
                    shares.forEach(s => {
                        const statusBadge = s.is_active
                            ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-green-50 text-green-700">Active</span>'
                            : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-stone-100 text-stone-500">Inactive</span>';
                        html += `<div class="border border-stone-200 rounded-lg px-3 py-2">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-xs font-medium text-stone-800">${s.name || 'Unnamed'}</span>
                                ${statusBadge}
                            </div>
                            <div class="flex items-center gap-1 mb-1.5">
                                <input type="text" value="${s.url}" readonly class="flex-1 h-6 px-2 text-[10px] font-mono bg-stone-50 border border-stone-200 rounded text-stone-600 focus:outline-none">
                                <button class="btn-copy-share h-6 px-2 text-[10px] font-semibold text-red-700 border border-red-200 rounded hover:bg-red-50" data-url="${s.url}">Copy</button>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-stone-400">
                                <span>Accessed ${s.access_count}x${s.last_accessed_at ? ' · Last: ' + s.last_accessed_at : ''}</span>
                                <div class="flex items-center gap-1">
                                    <button class="btn-toggle-share text-stone-500 hover:text-stone-700" data-page-id="${pageId}" data-share-id="${s.id}" title="${s.is_active ? 'Deactivate' : 'Activate'}">${s.is_active ? '⏸' : '▶'}</button>
                                    <button class="btn-delete-share text-red-400 hover:text-red-600" data-page-id="${pageId}" data-share-id="${s.id}" title="Delete">✕</button>
                                </div>
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                }
                $('#share-modal-body').html(html);
            }

        });
    </script>

    {{-- Share API Modal --}}
    <div id="share-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
        <div id="share-modal-bg" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-stone-800">Share Form via API</h3>
                <button id="share-modal-close" class="text-stone-400 hover:text-stone-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-[11px] text-stone-500 mb-3">Generate a public API link to share this form's structure with external users. They can access the form fields via a GET request — no login required.</p>
            <div id="share-modal-body"></div>
        </div>
    </div>
@endsection