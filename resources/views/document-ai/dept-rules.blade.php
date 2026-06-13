@extends('layouts.app')

@section('title', 'Department Prediction Rules')
@section('page-title', 'Document AI — Department Rules')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <a href="{{ route('document-ai.playground') }}" class="hover:text-stone-600">AI Doc Predictor</a>
    <span>/</span>
    <span class="text-stone-600">Dept Rules</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto" x-data="deptRulesApp()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">Department Prediction Rules</h2>
            <p class="text-sm text-stone-500 mt-1">Configure how the AI predicts which department a document belongs to</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Playground
            </a>
            <a href="{{ route('document-ai.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35"/></svg>
                Training Settings
            </a>
        </div>
    </div>

    {{-- Explanation --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <h4 class="text-sm font-semibold text-blue-800 mb-2">How Department Prediction Works</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-blue-700">
            <div class="bg-white/60 rounded-lg p-3">
                <p class="font-bold text-blue-800 mb-1">1. Document Type (Weight: 80)</p>
                <p>If the document is a "Credit Note" or "Invoice", it likely belongs to Finance. Strongest signal.</p>
            </div>
            <div class="bg-white/60 rounded-lg p-3">
                <p class="font-bold text-blue-800 mb-1">2. Vendor Keywords (Weight: 60)</p>
                <p>If vendor name contains "travel", "hotel" → Administration. "software", "cloud" → IT.</p>
            </div>
            <div class="bg-white/60 rounded-lg p-3">
                <p class="font-bold text-blue-800 mb-1">3. Content Keywords (Weight: 40)</p>
                <p>General terms in document body like "GST", "NEFT" → Finance. Weakest signal, used as tiebreaker.</p>
            </div>
        </div>
    </div>

    {{-- Add Rule Button --}}
    <div class="flex justify-end mb-4">
        <button @click="openModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Rule
        </button>
    </div>

    {{-- Rules grouped by department --}}
    @foreach($departments as $dept)
        @php $deptRules = $rules->get($dept->id, collect()); @endphp
        @if($deptRules->isNotEmpty())
        <div class="bg-white border border-stone-200 rounded-xl mb-4 overflow-hidden">
            <div class="px-5 py-3 bg-stone-50 border-b border-stone-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800">{{ $dept->department_name }} <span class="text-stone-400 font-normal">({{ $dept->department_code }})</span></h3>
                <span class="text-xs text-stone-500">{{ $deptRules->count() }} rules</span>
            </div>
            <div class="divide-y divide-stone-100">
                @foreach($deptRules->groupBy('rule_type') as $type => $typeRules)
                <div class="px-5 py-3">
                    <p class="text-[10px] uppercase font-medium tracking-wide mb-2
                        {{ $type === 'doc_type' ? 'text-red-600' : ($type === 'vendor_keyword' ? 'text-blue-600' : 'text-green-600') }}">
                        {{ str_replace('_', ' ', $type) }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($typeRules as $rule)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs border
                            {{ $type === 'doc_type' ? 'bg-red-50 border-red-200 text-red-700' : ($type === 'vendor_keyword' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-green-50 border-green-200 text-green-700') }}">
                            {{ $rule->pattern }}
                            <span class="text-[9px] opacity-60">({{ $rule->weight }})</span>
                            <button onclick="deleteRule({{ $rule->id }})" class="ml-0.5 opacity-40 hover:opacity-100">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    {{-- Add Rule Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
        <div @click.outside="showModal = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-stone-800 mb-4">Add Prediction Rule</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Department</label>
                    <select x-model="form.department_id" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2">
                        <option value="">Select department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->department_name }} ({{ $dept->department_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Rule Type</label>
                    <select x-model="form.rule_type" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2">
                        <option value="doc_type">Document Type (strongest — if doc type name found)</option>
                        <option value="vendor_keyword">Vendor Keyword (medium — vendor/party name match)</option>
                        <option value="content_keyword">Content Keyword (weakest — general body text)</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Pattern (keyword to match)</label>
                    <input type="text" x-model="form.pattern" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2"
                           placeholder="e.g. travel, cold storage, credit note">
                    <p class="text-[10px] text-stone-400 mt-1">Lowercase. If this text appears in the document, it scores for this department.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Weight (1-100)</label>
                    <input type="number" x-model="form.weight" min="1" max="100" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2">
                    <p class="text-[10px] text-stone-400 mt-1">Higher = stronger signal. Recommended: doc_type=80, vendor=60, content=40</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="showModal = false" class="px-4 py-2 text-sm text-stone-600 bg-stone-100 rounded-lg">Cancel</button>
                <button @click="saveRule()" class="px-4 py-2 text-sm text-white bg-red-700 rounded-lg hover:bg-red-800">Save Rule</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deptRulesApp() {
    return {
        showModal: false,
        form: { department_id: '', rule_type: 'vendor_keyword', pattern: '', weight: 60 },
        openModal() { this.form = { department_id: '', rule_type: 'vendor_keyword', pattern: '', weight: 60 }; this.showModal = true; },
        saveRule() {
            $.post('{{ route("document-ai.dept-rules.store") }}', { _token: '{{ csrf_token() }}', ...this.form }, (res) => {
                if (res.success) { this.showModal = false; location.reload(); }
            }).fail((xhr) => { alert(xhr.responseJSON?.message || 'Error'); });
        }
    };
}

function deleteRule(id) {
    if (!confirm('Delete this rule?')) return;
    $.ajax({ url: '{{ url("document-ai/dept-rules") }}/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' },
        success: function(res) { if (res.success) location.reload(); }
    });
}
</script>
@endpush
