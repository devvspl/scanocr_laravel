@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
#main-wrap{padding-top:32px}
.select2-container--default .select2-selection--single {height: 24px;border: 1px solid #d6d3d1;border-radius: .5rem;background: #fafaf9;display: flex;align-items: center;min-height: 24px}
.select2-container--default .select2-selection--single .select2-selection__rendered {font-size: .75rem;color: #292524;padding-left: .75rem;line-height: 34px}
.select2-container--default .select2-selection--single .select2-selection__arrow {height: 36px;right: .5rem}
.select2-container--default .select2-selection--single .select2-selection__placeholder {color: #a8a29e}
.select2-container--default .select2-results__option {font-size: .75rem;padding: .4rem .75rem}.select2-container--default .select2-results__option--highlighted {background: #7f1d1d;color: #fff}
.select2-search--dropdown .select2-search__field {font-size: .75rem;border: 1px solid #d6d3d1;border-radius: .375rem;padding: .3rem .5rem}
.select2-dropdown {border: 1px solid #d6d3d1;border-radius: .5rem;box-shadow: 0 4px 16px rgba(0, 0, 0, .08)}
.select2-container--open .select2-selection--single {border-color: #7f1d1d;box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)}.select2-container .select2-selection--single {height: 24px !important;border: 1px solid #d6d3d1 !important;border-radius: 0.5rem !important;background: #fafaf9 !important;padding: 0 !important}
.select2-container .select2-selection--single .select2-selection__clear {background-color: transparent;border: none;font-size: smaller;color: #888888;}
.select2-container--default .select2-selection--single .select2-selection__arrow {height: 26px;position: absolute;top: -4px;right: 1px;width: 20px;}
.select2-container .select2-selection--single .select2-selection__rendered {padding: 0 0 0 12px !important;line-height: 34px !important;font-size: 10px !important;color: #292524 !important}
.select2-container .select2-selection--single .select2-selection__arrow {height: 34px !important;right: 8px !important}
.select2-container .select2-selection--single .select2-selection__arrow b {border-width: 4px 4px 0 4px !important;margin-top: -2px !important}.entry-grid{display:grid;grid-template-columns:1fr;height:calc(100vh - 120px)}
.kpi-card{text-align:center;padding:.6rem;border-radius:.5rem;border:1px solid #e7e5e4}
.top-bar{display:flex;align-items:center;gap:.35rem;margin-bottom:.35rem}
.top-bar-fill{flex:1;height:5px;background:#e7e5e4;border-radius:3px;overflow:hidden}
.top-bar-fill span{display:block;height:100%;border-radius:3px}
.dash-table{width:100%;font-size:.7rem;border-collapse:collapse}
.dash-table th{background:#fafaf9;color:#78716c;font-size:.6rem;text-transform:uppercase;letter-spacing:.03em;padding:.4rem .6rem;text-align:left;border-bottom:2px solid #e7e5e4}
.dash-table td{padding:.4rem .6rem;border-bottom:1px solid #f5f5f4}
.dash-table tr:hover td{background:#fafaf9}
.skel{background:linear-gradient(90deg,#f5f5f4 25%,#e7e5e4 50%,#f5f5f4 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:.25rem;height:1rem}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
</style>
@endpush

@section('content')
<div x-data="dashboardApp()" x-init="init()">

    {{-- Loader Overlay --}}
    <template x-if="loading">
        <div class="flex items-center justify-center" style="min-height:70vh">
            <div style="text-align:center">
                <svg style="width:40px;height:40px;animation:spin 1s linear infinite;color:#7f1d1d;margin:0 auto" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <p style="margin-top:.75rem;font-size:.8rem;color:#78716c;font-weight:500">Loading dashboard...</p>
            </div>
        </div>
    </template>

    <div x-show="!loading">

    {{-- ══ Filters ═════════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-3">
        <div style="display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:#fafaf9;flex-wrap:nowrap">
            <svg class="shrink-0" style="width:14px;height:14px;color:#a8a29e" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <select id="f-fy" style="width:100px"></select>
            <select id="f-company" style="width:140px"></select>
            <select id="f-location" style="width:130px"></select>
            <select id="f-user" style="width:120px"></select>
            <select id="f-doctype" style="width:130px"></select>
            <input type="date" id="f-from" style="height:28px;padding:0 .4rem;font-size:.68rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;color:#292524;width:110px" onfocus="this.showPicker()">
            <input type="date" id="f-to" style="height:28px;padding:0 .4rem;font-size:.68rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;color:#292524;width:110px" onfocus="this.showPicker()">
            <button @click="loadData()" style="height:28px;display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:600;border-radius:.375rem;cursor:pointer;background:#7f1d1d;color:#fff;border:none;padding:0 .65rem;white-space:nowrap">
                <svg style="width:11px;height:11px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply
            </button>
            <button @click="resetFilters()" style="height:28px;display:inline-flex;align-items:center;font-size:.68rem;font-weight:600;border-radius:.375rem;background:#fff;color:#57534e;border:1px solid #d6d3d1;padding:0 .55rem;cursor:pointer;white-space:nowrap">Reset</button>
        </div>
    </div>

    {{-- ══ KPI Stage Status ════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4 mb-3" style="display:none">
        <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-3">Key Performance — Stage Status</h3>
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-2">
            <template x-for="k in kpiItems" :key="k.label">
                <div class="kpi-card" :style="'border-color:'+k.border">
                    <p class="text-lg font-bold" :style="'color:'+k.color" x-text="k.value"></p>
                    <p class="text-[9px] font-medium text-stone-500 mt-0.5" x-text="k.label"></p>
                </div>
            </template>
        </div>
    </div>

    {{-- ══ Charts Row: Monthly Trend (col-9) + Today Activity (col-3) ════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-3">
        <div class="lg:col-span-8 bg-white border border-stone-200 rounded-xl p-4">
            <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-3">Monthly Scan Trend</h3>
            <div style="height:220px"><canvas id="chartMonthly"></canvas></div>
        </div>
        <div class="lg:col-span-4 bg-white border border-stone-200 rounded-xl p-4 flex flex-col">
            <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-3">Today's Activity</h3>
            <div class="flex-1 overflow-y-auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead><tr>
                        <th style="text-align:left;font-size:.6rem;color:#78716c;font-weight:700;text-transform:uppercase;padding:.3rem 0;border-bottom:1px solid #e7e5e4">Section</th>
                        <th style="text-align:right;font-size:.6rem;color:#78716c;font-weight:700;text-transform:uppercase;padding:.3rem 0;border-bottom:1px solid #e7e5e4">Count</th>
                    </tr></thead>
                    <tbody id="tblToday"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══ Document Type Wise — Count Chart (full width) ═════════════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4 mb-3">
        <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-3">Document Type Wise</h3>
        <div style="height:400px"><canvas id="chartDocType"></canvas></div>
    </div>

    {{-- ══ Company Wise Report (full width, like super-scanner) ══════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-3 flex flex-col" style="max-height:420px">
        <div class="px-4 py-2.5 border-b border-stone-100 shrink-0"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide">Company Wise Report</h3></div>
        <div class="overflow-y-auto flex-1" id="tblCompany"></div>
    </div>

    {{-- ══ Document Receiving + Bill Approver ══════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden flex flex-col" style="max-height:400px">
            <div class="px-4 py-2.5 border-b border-stone-100 shrink-0"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide">Document Receiving Report</h3></div>
            <div class="overflow-y-auto flex-1" id="tblDocReceiving"></div>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden flex flex-col" style="max-height:400px">
            <div class="px-4 py-2.5 border-b border-stone-100 shrink-0"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide">Bill Approver Report</h3></div>
            <div class="overflow-y-auto flex-1" id="tblApprover"></div>
        </div>
    </div>

    {{-- ══ Top Lists ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Companies</h3><div id="topCompanies"></div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Document Types</h3><div id="topDocTypes"></div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Scanners</h3><div id="topScanners"></div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Punch Users</h3><div id="topPunchers"></div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Bill Approvers</h3><div id="topApprovers"></div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2">Top Locations</h3><div id="topLocations"></div></div>
    </div>
    </div>{{-- end x-show !loading --}}
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function dashboardApp() {
    return {
        kpiItems: [],
        chartMonthly: null, chartDocType: null,
        loading: true,

        init() {
            this.$nextTick(() => {
                this.initFilters();
                this.loadData();
            });
        },

        initFilters() {
            const s2 = (el, ph, url) => $(el).select2({ placeholder: ph, allowClear: true, width: '100%', ajax: { url, dataType: 'json', delay: 250, data: p => ({ q: p.term, page: p.page || 1 }), processResults: d => d } });
            s2('#f-fy', 'All FY', '/reports/select/financial-years');
            s2('#f-company', 'All Companies', '/reports/select/companies');
            s2('#f-location', 'All Locations', '/reports/select/locations');
            s2('#f-user', 'All Users', '/reports/select/users');
            s2('#f-doctype', 'All Doc Types', '/reports/select/doc-types');
        },

        resetFilters() {
            $('#f-fy,#f-company,#f-location,#f-user,#f-doctype').val(null).trigger('change');
            $('#f-from,#f-to').val('');
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    fy_id: $('#f-fy').val() || '',
                    company_id: $('#f-company').val() || '',
                    location_id: $('#f-location').val() || '',
                    user_id: $('#f-user').val() || '',
                    doc_type_id: $('#f-doctype').val() || '',
                    from_date: $('#f-from').val() || '',
                    to_date: $('#f-to').val() || '',
                });
                [...params.entries()].forEach(([k, v]) => { if (!v) params.delete(k); });

                const res = await fetch('/dashboard/data?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const d = await res.json();

                this.renderKPI(d.kpi);
                this.renderToday(d.today, d.extractionToday);
                this.renderMonthlyChart(d.monthly);
                this.renderDocTypeChart(d.topDocTypes);
                this.renderTable('tblCompany', ['Company', 'Total', 'Pend. Appr.', 'Approved', 'Rejected', 'Classified', 'Punched', 'Completed', 'Pend. Naming', 'Pend. Verify'], d.companyWise, ['label', 'total', 'pending_approval', 'bill_approved', 'bill_rejected', 'classified', 'punched', 'completed', 'pending_naming', 'pending_verification']);
                this.renderTable('tblDocReceiving', ['Company', 'Total', 'Received', 'Pend. Verify', 'Pend. Naming', 'Today'], d.docReceiving, ['label', 'total_scans', 'received', 'pending_verification', 'pending_naming', 'received_today']);
                this.renderApproverTable(d.billApprover);
                this.renderTopList('topDocTypes', d.topDocTypes, '#6366f1');
                this.renderTopList('topScanners', d.topScanners, '#2563eb');
                this.renderTopList('topPunchers', d.topPunchers, '#9333ea');
                this.renderTopList('topApprovers', d.topApprovers, '#d97706');
                this.renderTopList('topLocations', d.topLocations, '#7f1d1d');
                this.renderTopList('topCompanies', d.topCompanies, '#0d9488');
            } catch(e) { console.error('Dashboard load error:', e); }
            this.loading = false;
        },

        renderKPI(k) {
            this.kpiItems = [
                { label: 'Total Scans', value: k.total || 0, color: '#1c1917', border: '#e7e5e4' },
                { label: 'Pending Naming', value: k.pending_naming || 0, color: '#78716c', border: '#d6d3d1' },
                { label: 'Pending Verification', value: k.pending_verification || 0, color: '#525252', border: '#a3a3a3' },
                { label: 'Doc Received', value: k.document_received || 0, color: '#0f766e', border: '#99f6e4' },
                { label: 'Bill Approval Pending', value: k.pending_bill_approval || 0, color: '#d97706', border: '#fde68a' },
                { label: 'Classification Pending', value: k.pending_classification || 0, color: '#2563eb', border: '#bfdbfe' },
                { label: 'Extraction Done', value: k.extracted_total || 0, color: '#0891b2', border: '#a5f3fc' },
                { label: 'Extraction Pending', value: k.pending_extraction || 0, color: '#6366f1', border: '#c7d2fe' },
                { label: 'Punching Pending', value: k.pending_punching || 0, color: '#9333ea', border: '#e9d5ff' },
                { label: 'Punch Approval Pending', value: k.pending_punch_approval || 0, color: '#ea580c', border: '#fed7aa' },
                { label: 'Completed', value: k.completed || 0, color: '#16a34a', border: '#bbf7d0' },
                { label: 'Rejected', value: k.rejected || 0, color: '#dc2626', border: '#fecaca' },
            ];
        },

        renderToday(t, extractionToday) {
            const rows = [
                { section: 'Scanned', count: t?.scanned || 0, color: '#2563eb' },
                { section: 'Document Received / Verified', count: t?.doc_received || 0, color: '#0f766e' },
                { section: 'Bill Approved', count: t?.bill_approved || 0, color: '#d97706' },
                { section: 'Classified', count: t?.classified || 0, color: '#4f46e5' },
                { section: 'Extraction Completed', count: extractionToday || 0, color: '#0891b2' },
                { section: 'Data Punched', count: t?.punched || 0, color: '#9333ea' },
                { section: 'Final Approved', count: t?.final_approved || 0, color: '#16a34a' },
            ];
            const el = document.getElementById('tblToday');
            if (!el) return;
            el.innerHTML = rows.map(r =>
                `<tr><td style="padding:.4rem 0;font-size:.68rem;color:#44403c;border-bottom:1px solid #f5f5f4;white-space:nowrap"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${r.color};margin-right:6px;vertical-align:middle"></span>${r.section}</td><td style="padding:.4rem 0;font-size:.72rem;font-weight:700;color:${r.color};text-align:right;border-bottom:1px solid #f5f5f4">${r.count}</td></tr>`
            ).join('');
        },

        renderMonthlyChart(data) {
            if (this.chartMonthly) { this.chartMonthly.destroy(); this.chartMonthly = null; }
            const el = document.getElementById('chartMonthly');
            if (!el) return;
            const colors = ['#7f1d1d', '#2563eb', '#16a34a', '#d97706', '#9333ea'];
            const datasets = (data.datasets || []).map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                backgroundColor: colors[i % colors.length] + '20',
                borderColor: colors[i % colors.length],
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: colors[i % colors.length],
            }));
            this.chartMonthly = new Chart(el, {
                type: 'line',
                data: { labels: data.labels || [], datasets },
                options: { responsive: true, maintainAspectRatio: false, animation: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } }, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } } }
            });
        },

        renderDocTypeChart(data) {
            if (this.chartDocType) { this.chartDocType.destroy(); this.chartDocType = null; }
            const el = document.getElementById('chartDocType');
            if (!el || !data || data.length === 0) return;
            const colors = ['#7f1d1d','#b91c1c','#dc2626','#ef4444','#f87171','#fb923c','#fbbf24','#a3e635','#22c55e','#06b6d4','#3b82f6','#6366f1','#8b5cf6','#a855f7','#d946ef'];
            this.chartDocType = new Chart(el, {
                type: 'bar',
                data: { labels: data.map(d => d.label), datasets: [{ label: 'Count', data: data.map(d => d.total), backgroundColor: colors.slice(0, data.length), borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { font: { size: 9 } } }, x: { ticks: { font: { size: 9 }, maxRotation: 45 } } } }
            });
        },

        renderTable(id, headers, rows, keys) {
            let html = '<table class="dash-table"><thead style="position:sticky;top:0;z-index:1"><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead><tbody>';
            if (!rows || rows.length === 0) {
                html += `<tr><td colspan="${headers.length}" style="text-align:center;color:#a8a29e;padding:1.5rem">No data</td></tr>`;
            } else {
                rows.forEach(r => { html += '<tr>' + keys.map((k, i) => `<td${i > 0 ? ' style="text-align:center"' : ''}>${r[k] ?? '—'}</td>`).join('') + '</tr>'; });
                // Grand Total row — calculate percentages from summed values
                const sums = {};
                keys.forEach((k, i) => { if (i > 0) sums[k] = rows.reduce((a, r) => a + (parseFloat(r[k]) || 0), 0); });
                html += '<tr style="background:#fef2f2;font-weight:700;border-top:2px solid #7f1d1d;position:sticky;bottom:0">';
                keys.forEach((k, i) => {
                    if (i === 0) { html += '<td style="font-weight:700;color:#7f1d1d">Grand Total</td>'; }
                    else if (k === 'approve_rate') {
                        const rate = sums['total'] > 0 ? ((sums['approved'] / sums['total']) * 100).toFixed(1) : '0.0';
                        html += `<td style="text-align:center;font-weight:700;color:#7f1d1d">${rate}</td>`;
                    } else if (k === 'reject_rate') {
                        const rate = sums['total'] > 0 ? ((sums['rejected'] / sums['total']) * 100).toFixed(1) : '0.0';
                        html += `<td style="text-align:center;font-weight:700;color:#7f1d1d">${rate}</td>`;
                    } else {
                        html += `<td style="text-align:center;font-weight:700;color:#7f1d1d">${Math.round(sums[k]).toLocaleString()}</td>`;
                    }
                });
                html += '</tr>';
            }
            html += '</tbody></table>';
            document.getElementById(id).innerHTML = html;
        },

        renderApproverTable(rows) {
            const el = document.getElementById('tblApprover');
            if (!el) return;
            let html = '<table class="dash-table"><thead style="position:sticky;top:0;z-index:1"><tr><th>Approver</th><th style="text-align:center">Total</th><th style="text-align:center">Approved</th><th style="text-align:center">Rejected</th><th style="text-align:center">Pending</th><th style="text-align:center">Rate</th></tr></thead><tbody>';
            if (!rows || rows.length === 0) {
                html += '<tr><td colspan="6" style="text-align:center;color:#a8a29e;padding:1.5rem">No data</td></tr>';
            } else {
                rows.forEach(r => {
                    const ar = r.approve_rate || 0;
                    const rr = r.reject_rate || 0;
                    html += `<tr><td>${r.label}</td><td style="text-align:center">${r.total}</td><td style="text-align:center">${r.approved}</td><td style="text-align:center">${r.rejected}</td><td style="text-align:center">${r.pending}</td><td style="text-align:center"><span style="color:#16a34a;font-weight:600">${ar}%</span> <span style="color:#a8a29e">/</span> <span style="color:#dc2626;font-weight:600">${rr}%</span></td></tr>`;
                });
                // Grand Total
                const sTotal = rows.reduce((a, r) => a + (parseInt(r.total) || 0), 0);
                const sAppr = rows.reduce((a, r) => a + (parseInt(r.approved) || 0), 0);
                const sRej = rows.reduce((a, r) => a + (parseInt(r.rejected) || 0), 0);
                const sPend = rows.reduce((a, r) => a + (parseInt(r.pending) || 0), 0);
                const gAr = sTotal > 0 ? ((sAppr / sTotal) * 100).toFixed(1) : '0.0';
                const gRr = sTotal > 0 ? ((sRej / sTotal) * 100).toFixed(1) : '0.0';
                html += `<tr style="background:#fef2f2;font-weight:700;border-top:2px solid #7f1d1d;position:sticky;bottom:0"><td style="color:#7f1d1d">Grand Total</td><td style="text-align:center;color:#7f1d1d">${sTotal.toLocaleString()}</td><td style="text-align:center;color:#7f1d1d">${sAppr.toLocaleString()}</td><td style="text-align:center;color:#7f1d1d">${sRej.toLocaleString()}</td><td style="text-align:center;color:#7f1d1d">${sPend.toLocaleString()}</td><td style="text-align:center"><span style="color:#16a34a;font-weight:700">${gAr}%</span> <span style="color:#a8a29e">/</span> <span style="color:#dc2626;font-weight:700">${gRr}%</span></td></tr>`;
            }
            html += '</tbody></table>';
            el.innerHTML = html;
        },

        renderTopList(id, data, color) {
            if (!data || data.length === 0) { document.getElementById(id).innerHTML = '<p style="font-size:.7rem;color:#a8a29e;text-align:center;padding:1rem">No data</p>'; return; }
            const max = Math.max(...data.map(d => d.total), 1);
            let html = '';
            data.forEach(d => {
                const pct = Math.round((d.total / max) * 100);
                html += `<div class="top-bar"><span style="font-size:.65rem;color:#44403c;width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${d.label || '—'}</span><div class="top-bar-fill"><span style="width:${pct}%;background:${color}"></span></div><span style="font-size:.6rem;font-weight:700;color:#292524;width:28px;text-align:right">${d.total}</span></div>`;
            });
            document.getElementById(id).innerHTML = html;
        },
    };
}
</script>
@endpush
