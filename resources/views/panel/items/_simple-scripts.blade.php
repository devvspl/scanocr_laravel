@push('scripts')
<script>
function simpleListPage() {
    return {
        modalOpen: false, editId: null, saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {}, form: {},
        init() {
            this.resetForm();
            window._openModal = (data) => this.openModal(data);
        },
        resetForm() {
            this.form = { name: '', description: '', symbol: '', type: '', is_active: true };
        },
        openModal(data = null) {
            this.errors = {}; this.toast = { show: false, type: 'success', message: '' };
            if (data) {
                this.editId = data.id;
                this.form = { ...data, is_active: data.is_active };
            } else {
                this.editId = null; this.resetForm();
            }
            this.modalOpen = true;
        },
        closeModal() { this.modalOpen = false; },
        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
        async save() {
            this.saving = true; this.errors = {};
            const url = this.editId ? `{{ $storeUrl }}/${this.editId}` : '{{ $storeUrl }}';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0 }),
                });
                const json = await res.json();
                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
                        this.errors = flat;
                        this.showToast('error', 'Please fix the errors below.');
                    } else this.showToast('error', json.message ?? 'Something went wrong.');
                    return;
                }
                this.closeModal();
                window._dataTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },
    };
}
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    
    const columns = [
        @php
            $cols = $columns ?? [];
        @endphp
        @foreach($cols as $index => $col)
        {
            data: '{{ $col["data"] }}',
            className: '{{ ($col["center"] ?? false) ? "td-center" : "" }}',
            render: function(v, t, row) {
                @if($col['data'] === 'is_active')
                return v ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>';
                @elseif($col['data'] === 'created_by_name')
                if (!v || v === '—') return '<span class="text-stone-400">—</span>';
                return '<span class="inline-flex items-center gap-1 text-xs text-stone-600"><svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>' + v + '</span>';
                @else
                return v ?? '<span class="text-stone-400">—</span>';
                @endif
            }
        },
        @endforeach
        {
            data: 'id',
            orderable: false,
            className: 'td-center',
            render: function(id) {
                return '<div class="act-group justify-center"><button class="act-btn act-edit btn-edit" data-id="' + id + '"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button class="act-btn act-delete btn-delete" data-id="' + id + '"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>';
            }
        }
    ];
    
    const table = $('#data-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ $dataRoute }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: columns,
        order: [[0, 'asc']],
        pageLength: 15,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
    });
    
    window._dataTable = table;
    $('#filter-status').on('change', () => table.ajax.reload(null, false));
    
    $('#data-table').on('click', '.btn-edit', async function () {
        const id = $(this).data('id');
        const res = await fetch(`{{ $storeUrl }}/${id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        window._openModal(data);
    });
    
    $('#data-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this item?')) return;
        const id = $(this).data('id');
        const res = await fetch(`{{ $storeUrl }}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) {
            table.ajax.reload(null, false);
            _showGlobalToast('success', json.message ?? 'Deleted.');
        } else {
            _showGlobalToast('error', json.message ?? 'Failed to delete.');
        }
    });
    
    window._showGlobalToast = function (type, message) {
        const el = document.createElement('div');
        el.className = 'fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ' + (type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700');
        el.innerHTML = '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + (type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z') + '"/></svg><span>' + message + '</span>';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
