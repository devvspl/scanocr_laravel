@extends('layouts.app')

@section('title', 'Company Info')
@section('page-title', 'Company Info')

@section('content')
<div x-data="companyPage()" x-init="init()">

    {{-- Settings nav --}}
    @include('panel.settings._nav')

    {{-- Company list --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Companies</h3>
                <p class="text-xs text-stone-400 mt-0.5">{{ $companies->count() }} {{ Str::plural('company', $companies->count()) }} registered</p>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Company
            </button>
        </div>

        @if($companies->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-stone-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">No companies yet</p>
            <p class="text-xs text-stone-400 mt-1 mb-4">Add your first company to get started.</p>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Company
            </button>
        </div>
        @else
        <div class="divide-y divide-stone-100">
            @foreach($companies as $company)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-stone-50 transition-colors group">

                {{-- Logo / Avatar --}}
                @php
                    $words    = preg_split('/[\s.]+/', $company->display_name ?? $company->name);
                    $words    = array_filter($words, fn($w) => strlen($w) > 1 && !in_array(strtolower($w), ['pvt','ltd','llp','inc','co','and','the','of']));
                    $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(array_values($words), 0, 2))));
                    $initials = $initials ?: strtoupper(substr($company->name, 0, 2));
                @endphp
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 overflow-hidden tracking-wide"
                     style="background: linear-gradient(135deg, #991b1b, #450a0a)">
                    @if($company->logo)
                        <img src="{{ Storage::url($company->logo) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                    @else
                        {{ $initials }}
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-stone-800">{{ $company->name }}</span>
                        @if($company->is_default)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Default
                        </span>
                        @endif
                        @if(!$company->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                        @if($company->legal_name && $company->legal_name !== $company->name)
                        <span class="text-xs text-stone-400">{{ $company->legal_name }}</span>
                        @endif
                        @if($company->gstin)
                        <span class="text-xs text-stone-400 font-mono">GST: {{ $company->gstin }}</span>
                        @endif
                        @if($company->email)
                        <span class="text-xs text-stone-400">{{ $company->email }}</span>
                        @endif
                        @if($company->city || $company->state)
                        <span class="text-xs text-stone-400">{{ implode(', ', array_filter([$company->city, $company->state])) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Type badge --}}
                <div class="hidden sm:block shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-stone-100 text-stone-600">
                        {{ str_replace('_', ' ', ucwords($company->type, '_')) }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if(!$company->is_default)
                    <button @click="setDefault({{ $company->id }})"
                            title="Set as default"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-amber-50 hover:text-amber-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </button>
                    @endif
                    <button @click="openModal({{ $company->id }})"
                            title="Edit"
                            class="act-btn act-edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="deleteCompany({{ $company->id }}, '{{ addslashes($company->name) }}')"
                            title="Delete"
                            class="act-btn act-delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Offcanvas ── --}}
    <div x-show="panelOpen" x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-full max-w-2xl bg-white shadow-2xl z-50 flex flex-col"
         style="display:none">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Company' : 'Add Company'"></h3>
                <p class="text-xs text-stone-400 mt-0.5" x-text="editId ? 'Update company details' : 'Fill in the company information'"></p>
            </div>
            <button @click="closePanel()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-transition
             :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
             class="mx-5 mt-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shrink-0"
             style="display:none">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
            </svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Form tabs + fields --}}
        @include('panel.settings._company-form')

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between gap-3 shrink-0 bg-stone-50">
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                    <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                </label>
                <span class="text-sm text-stone-600 font-medium">Active</span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="closePanel()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="editId ? 'Save Changes' : 'Create Company'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Overlay --}}
    <div x-show="panelOpen" x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="closePanel()"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display:none">
    </div>

</div>
@endsection

@push('scripts')
<script>
function companyPage() {
    return {
        panelOpen: false,
        editId: null,
        saving: false,
        tab: 'identity',
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: {},

        init() {
            this.resetForm();
        },

        resetForm() {
            this.form = {
                name: '', legal_name: '', display_name: '', code: '', type: 'private_limited',
                industry: '', website: '', email: '', phone: '', mobile: '', fax: '', description: '',
                address_line1: '', address_line2: '', city: '', state: '', country: 'India', pincode: '',
                gstin: '', pan: '', tan: '', cin: '', msme_number: '',
                gst_registration_type: 'regular', gst_registration_date: '',
                bank_name: '', bank_branch: '', bank_account_number: '', bank_ifsc: '',
                bank_swift: '', bank_account_type: '',
                fy_start_month: '04', currency_code: 'INR', currency_symbol: '₹',
                date_format: 'DD/MM/YYYY', timezone: 'Asia/Kolkata',
                is_active: true,
            };
        },

        async openModal(id = null) {
            this.errors = {};
            this.tab = 'identity';
            this.toast = { show: false, type: 'success', message: '' };

            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/company/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await res.json();
                this.form  = {
                    name:                  data.name                  ?? '',
                    legal_name:            data.legal_name            ?? '',
                    display_name:          data.display_name          ?? '',
                    code:                  data.code                  ?? '',
                    type:                  data.type                  ?? 'private_limited',
                    industry:              data.industry              ?? '',
                    website:               data.website               ?? '',
                    email:                 data.email                 ?? '',
                    phone:                 data.phone                 ?? '',
                    mobile:                data.mobile                ?? '',
                    fax:                   data.fax                   ?? '',
                    description:           data.description           ?? '',
                    address_line1:         data.address_line1         ?? '',
                    address_line2:         data.address_line2         ?? '',
                    city:                  data.city                  ?? '',
                    state:                 data.state                 ?? '',
                    country:               data.country               ?? 'India',
                    pincode:               data.pincode               ?? '',
                    gstin:                 data.gstin                 ?? '',
                    pan:                   data.pan                   ?? '',
                    tan:                   data.tan                   ?? '',
                    cin:                   data.cin                   ?? '',
                    msme_number:           data.msme_number           ?? '',
                    gst_registration_type: data.gst_registration_type ?? 'regular',
                    gst_registration_date: data.gst_registration_date ?? '',
                    bank_name:             data.bank_name             ?? '',
                    bank_branch:           data.bank_branch           ?? '',
                    bank_account_number:   data.bank_account_number   ?? '',
                    bank_ifsc:             data.bank_ifsc             ?? '',
                    bank_swift:            data.bank_swift            ?? '',
                    bank_account_type:     data.bank_account_type     ?? '',
                    fy_start_month:        data.fy_start_month        ?? '04',
                    currency_code:         data.currency_code         ?? 'INR',
                    currency_symbol:       data.currency_symbol       ?? '₹',
                    date_format:           data.date_format           ?? 'DD/MM/YYYY',
                    timezone:              data.timezone              ?? 'Asia/Kolkata',
                    is_active:             data.is_active,
                };
            } else {
                this.editId = null;
                this.resetForm();
            }
            this.panelOpen = true;
        },

        closePanel() {
            this.panelOpen = false;
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        async save() {
            this.saving = true;
            this.errors = {};
            const url    = this.editId ? `/settings/company/${this.editId}` : '/settings/company';
            const method = this.editId ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0 }),
                });
                const json = await res.json();

                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
                        this.errors = flat;
                        // Jump to the tab with the first error
                        const firstErr = Object.keys(flat)[0];
                        const tabMap = {
                            identity: ['name','legal_name','display_name','code','type','industry','website','email','phone','mobile','fax','description'],
                            address:  ['address_line1','address_line2','city','state','country','pincode'],
                            tax:      ['gstin','pan','tan','cin','msme_number','gst_registration_type','gst_registration_date'],
                            bank:     ['bank_name','bank_branch','bank_account_number','bank_ifsc','bank_swift','bank_account_type'],
                            finance:  ['fy_start_month','currency_code','currency_symbol','date_format','timezone'],
                        };
                        for (const [t, fields] of Object.entries(tabMap)) {
                            if (fields.includes(firstErr)) { this.tab = t; break; }
                        }
                        this.showToast('error', 'Please fix the errors below.');
                    } else {
                        this.showToast('error', json.message ?? 'Something went wrong.');
                    }
                    return;
                }

                this.closePanel();
                window.location.reload();
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async setDefault(id) {
            const res  = await fetch(`/settings/company/${id}/default`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) window.location.reload();
        },

        async deleteCompany(id, name) {
            if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
            const res  = await fetch(`/settings/company/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                window.location.reload();
            } else {
                alert(json.message ?? 'Failed to delete.');
            }
        },
    };
}
</script>
@endpush
