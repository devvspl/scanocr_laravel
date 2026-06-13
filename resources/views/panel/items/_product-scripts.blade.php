@push('scripts')
<script>
function productPage() {
    return {
        modalOpen: false, editId: null, saving: false, tab: 'basic',
        toast: { show: false, type: 'success', message: '' }, errors: {}, form: {},
        init() {
            this.resetForm();
            window._openProductModal = (data) => this.openModal(data);
        },
        resetForm() {
            this.form = { code: '', name: '', description: '', type: 'goods', item_group_id: '', unit_id: '', hsn_sac: '', sale_price: '', purchase_price: '', tax_rate: '', opening_stock: '', reorder_level: '', track_inventory: true, is_active: true };
        },
        openModal(data = null) {
            this.errors = {}; this.tab = 'basic'; this.toast = { show: false, type: 'success', message: '' };
            if (data) {
                this.editId = data.id;
                this.form = { code: data.code ?? '', name: data.name ?? '', description: data.description ?? '', type: data.type ?? 'goods', item_group_id: data.item_group_id ? String(data.item_group_id) : '', unit_id: data.unit_id ? String(data.unit_id) : '', hsn_sac: data.hsn_sac ?? '', sale_price: data.sale_price ?? '', purchase_price: data.purchase_price ?? '', tax_rate: data.tax_rate ?? '', opening_stock: data.opening_stock ?? '', reorder_level: data.reorder_level ?? '', track_inventory: data.track_inventory, is_active: data.is_active };
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
            const url = this.editId ? `/master/products/${this.editId}` : '/master/products';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0, track_inventory: this.form.track_inventory ? 1 : 0 }),
                });
                const json = await res.json();
                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
                        this.errors = flat;
                        const pricingFields = ['sale_price','purchase_price','tax_rate'];
                        const inventoryFields = ['opening_stock','reorder_level','track_inventory'];
                        const firstErr = Object.keys(flat)[0];
                        if (pricingFields.includes(firstErr)) this.tab = 'pricing';
                        else if (inventoryFields.includes(firstErr)) this.tab = 'inventory';
                        else this.tab = 'basic';
                        this.showToast('error', 'Please fix the errors below.');
                    } else this.showToast('error', json.message ?? 'Something went wrong.');
                    return;
                }
                this.closeModal();
                window._productTable?.ajax.reload(null, false);
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
    const table = $('#product-table').DataTable({
        serverSide: true, processing: true,
        ajax: { url: '{{ $dataRoute }}', data(d) { d.group = $('#filter-group').val(); d.unit = $('#filter-unit').val(); d.type = $('#filter-type').val(); d.status = $('#filter-status').val(); } },
        columns: [
            { data: 'code', className: 'td-num' },
            { data: 'name', className: 'td-name' },
            { data: 'type', render(v) { return v === 'goods' ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700">Goods</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">Service</span>'; } },
            { data: 'sale_price', className: 'td-num' },
            { data: 'purchase_price', className: 'td-num' },
            { data: 'tax_rate', className: 'td-num', render(v) { return v + '%'; } },
            { data: 'created_by_name', render(v) { if (!v || v === '—') return '<span class="text-stone-400">—</span>'; return `<span class="inline-flex items-center gap-1 text-xs text-stone-600"><svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>${v}</span>`; } },
            { data: 'is_active', className: 'td-center', render(v) { return v ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>'; } },
            { data: 'id', orderable: false, className: 'td-center', render(id) { return `<div class="act-group justify-center"><button class="act-btn act-edit btn-edit" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button class="act-btn act-delete btn-delete" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>`; } },
        ],
        order: [[1, 'asc']], pageLength: 15, pagingType: 'simple_numbers', dom: '<"top"lf>t<"bottom"ip>',
    });
    window._productTable = table;
    $('#filter-group, #filter-unit, #filter-type, #filter-status').on('change', () => table.ajax.reload(null, false));
    $('#product-table').on('click', '.btn-edit', async function () {
        const id = $(this).data('id');
        const res = await fetch(`/master/products/${id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        window._openProductModal(data);
    });
    $('#product-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this product?')) return;
        const id = $(this).data('id');
        const res = await fetch(`/master/products/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message ?? 'Deleted.'); }
    });
    window._showGlobalToast = function (type, message) {
        const el = document.createElement('div');
        el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
