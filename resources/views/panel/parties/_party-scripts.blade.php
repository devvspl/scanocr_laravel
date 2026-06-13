{{-- Params: $type ('customer'|'vendor'), $dataRoute --}}
@push('scripts')
<script>
function partyPage() {
    return {
        modalOpen: false,
        editId: null,
        saving: false,
        tab: 'basic',
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: {},

        init() {
            this.resetForm();
            window._openPartyModal = (data) => this.openModal(data);
        },

        resetForm() {
            this.form = {
                code: '', name: '', display_name: '', email: '',
                phone: '', mobile: '', gstin: '', pan: '',
                billing_address: '', shipping_address: '',
                city: '', state: '', country: 'India', pincode: '',
                opening_balance: '', balance_type: 'debit',
                credit_limit: '', credit_days: '',
                account_group_id: '', is_active: true, notes: '',
            };
        },

        openModal(data = null) {
            this.errors = {};
            this.tab    = 'basic';
            this.toast  = { show: false, type: 'success', message: '' };

            if (data) {
                this.editId = data.id;
                this.form = {
                    code:             data.code             ?? '',
                    name:             data.name             ?? '',
                    display_name:     data.display_name     ?? '',
                    email:            data.email            ?? '',
                    phone:            data.phone            ?? '',
                    mobile:           data.mobile           ?? '',
                    gstin:            data.gstin            ?? '',
                    pan:              data.pan              ?? '',
                    billing_address:  data.billing_address  ?? '',
                    shipping_address: data.shipping_address ?? '',
                    city:             data.city             ?? '',
                    state:            data.state            ?? '',
                    country:          data.country          ?? 'India',
                    pincode:          data.pincode          ?? '',
                    opening_balance:  data.opening_balance  ?? '',
                    balance_type:     data.balance_type     ?? 'debit',
                    credit_limit:     data.credit_limit     ?? '',
                    credit_days:      data.credit_days      ?? '',
                    account_group_id: data.account_group_id ? String(data.account_group_id) : '',
                    is_active:        data.is_active,
                    notes:            data.notes            ?? '',
                };
            } else {
                this.editId = null;
                this.resetForm();
            }
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async save() {
            this.saving = true;
            this.errors = {};

            const base   = '/master/{{ $type }}s';
            const url    = this.editId ? `${base}/${this.editId}` : base;
            const method = this.editId ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        is_active:       this.form.is_active ? 1 : 0,
                        opening_balance: this.form.opening_balance || 0,
                        credit_days:     this.form.credit_days || null,
                    }),
                });

                const json = await res.json();

                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) {
                            flat[k] = Array.isArray(v) ? v[0] : v;
                        }
                        this.errors = flat;
                        // Switch to the tab that has the first error
                        const addressFields   = ['billing_address','shipping_address','city','state','country','pincode'];
                        const financialFields = ['opening_balance','balance_type','credit_limit','credit_days','account_group_id'];
                        const firstErr = Object.keys(flat)[0];
                        if (addressFields.includes(firstErr))        this.tab = 'address';
                        else if (financialFields.includes(firstErr)) this.tab = 'financial';
                        else                                          this.tab = 'basic';
                        this.showToast('error', 'Please fix the errors below.');
                    } else {
                        this.showToast('error', json.message ?? 'Something went wrong.');
                    }
                    return;
                }

                this.closeModal();
                window._partyTable?.ajax.reload(null, false);
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

    const table = $('#party-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ $dataRoute }}',
            data(d) {
                d.status = $('#filter-status').val();
            },
        },
        columns: [
            { data: 'code',  className: 'td-num' },
            {
                data: 'name',
                render(v, t, row) {
                    const sub = row.display_name ? `<span class="text-[10px] text-stone-400 block">${row.display_name}</span>` : '';
                    return `<span class="font-medium text-stone-800">${v}</span>${sub}`;
                },
            },
            {
                data: 'phone',
                render(v, t, row) {
                    const parts = [v, row.mobile].filter(Boolean);
                    return parts.length ? parts.join(' / ') : '<span class="text-stone-400">—</span>';
                },
            },
            {
                data: 'email',
                render(v) {
                    return v ? `<a href="mailto:${v}" class="text-red-700 hover:underline text-xs">${v}</a>` : '<span class="text-stone-400">—</span>';
                },
            },
            {
                data: 'opening_balance',
                className: 'td-num',
                render(v, t, row) {
                    const sign = row.balance_type === 'credit' ? 'Cr' : 'Dr';
                    return `${v} <span class="text-[10px] text-stone-400">${sign}</span>`;
                },
            },
            {
                data: 'created_by_name',
                render(v) {
                    if (!v || v === '—') return '<span class="text-stone-400">—</span>';
                    return `<span class="inline-flex items-center gap-1 text-xs text-stone-600">
                        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>${v}</span>`;
                },
            },
            {
                data: 'is_active',
                className: 'td-center',
                render(v) {
                    return v
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>';
                },
            },
            {
                data: 'id',
                orderable: false,
                className: 'td-center',
                render(id) {
                    return `<div class="act-group justify-center">
                        <button class="act-btn act-edit btn-edit" data-id="${id}" title="Edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>`;
                },
            },
        ],
        order: [[1, 'asc']],
        pageLength: 15,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        drawCallback() {
            const total = this.api().page.info().recordsTotal;
            if (total === 0) {
                $('#party-table, .dataTables_wrapper .bottom').hide();
                $('#empty-state').removeClass('hidden').addClass('flex');
            } else {
                $('#party-table, .dataTables_wrapper .bottom').show();
                $('#empty-state').removeClass('flex').addClass('hidden');
            }
        },
    });

    window._partyTable = table;

    $('#filter-status').on('change', () => table.ajax.reload(null, false));

    // Edit
    $('#party-table').on('click', '.btn-edit', async function () {
        const id  = $(this).data('id');
        const res = await fetch(`/master/{{ $type }}s/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        window._openPartyModal(data);
    });

    // Delete
    $('#party-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this {{ $type }}?')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/master/{{ $type }}s/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            table.ajax.reload(null, false);
            _showGlobalToast('success', json.message ?? 'Deleted.');
        }
    });

    // Global toast
    window._showGlobalToast = function (type, message) {
        const el = document.createElement('div');
        el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg
            ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/>
        </svg><span>${message}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
