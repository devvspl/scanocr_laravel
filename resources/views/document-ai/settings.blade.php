@extends('layouts.app')

@section('title', 'AI Training Settings')
@section('page-title', 'Document AI — Training Settings')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <a href="{{ route('document-ai.playground') }}" class="hover:text-stone-600">AI Doc Predictor</a>
    <span>/</span>
    <span class="text-stone-600">Settings</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto" x-data="settingsApp()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">Document AI Training Settings</h2>
            <p class="text-sm text-stone-500 mt-1">Manage training data for each document type</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Playground
            </a>
            <a href="{{ route('document-ai.logs') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Logs
            </a>
            <a href="{{ route('document-ai.analytics') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Analytics
            </a>
            <a href="{{ route('document-ai.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-red-700 rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </div>
    </div>

    {{-- Document Table --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 border-b border-stone-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">#</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Document Type</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Module</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Training Samples</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach($types as $index => $type)
                <tr class="hover:bg-stone-50 transition-colors">
                    <td class="px-5 py-3 text-stone-500">{{ $index + 1 }}</td>
                    <td class="px-5 py-3 font-medium text-stone-800">{{ $type->label }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 bg-stone-100 text-stone-600 text-xs font-medium rounded-full">
                            {{ ucfirst($type->module ?? 'general') }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-full">
                            {{ $type->active_training_count }} active
                        </span>
                        @if($type->training_data_count > $type->active_training_count)
                            <span class="text-xs text-stone-400 ml-1">({{ $type->training_data_count }} total)</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                     {{ $type->is_active ? 'bg-green-50 text-green-700' : 'bg-stone-100 text-stone-500' }}">
                            <span class="w-2 h-2 rounded-full {{ $type->is_active ? 'bg-green-500' : 'bg-stone-400' }}"></span>
                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="toggleTrainingSection({{ $type->id }})"
                                    class="p-1.5 text-stone-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="View Training Data">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                {{-- Training Data Expandable Section --}}
                <tr id="training-section-{{ $type->id }}" class="hidden">
                    <td colspan="6" class="px-5 py-4 bg-stone-50/50">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-stone-700">Training Data — {{ $type->label }}</h4>
                            <button @click="openTrainingModal({{ $type->id }}, '{{ addslashes($type->label) }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Sample
                            </button>
                        </div>
                        <div id="training-list-{{ $type->id }}" class="space-y-2">
                            <p class="text-xs text-stone-400 italic">Click to load...</p>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Training Data Modal --}}
    <div x-show="trainingModal" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
        <div @click.outside="trainingModal = false"
             class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
            <h3 class="text-lg font-semibold text-stone-800 mb-4" x-text="editTraining ? 'Edit Training Data' : 'Add Training Sample — ' + trainingTypeName"></h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Sample Text</label>
                    <textarea x-model="trainingForm.sample_text" rows="5"
                              class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none resize-y"
                              placeholder="Paste OCR text or type sample text for this document type..."></textarea>
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Keywords (comma-separated)</label>
                    <input type="text" x-model="trainingForm.keywords"
                           class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none"
                           placeholder="e.g. invoice, bill to, amount due, gstin">
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Title Patterns (comma-separated)</label>
                    <input type="text" x-model="trainingForm.title_patterns"
                           class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none"
                           placeholder="e.g. debit note, debit memo, DN No">
                    <p class="text-[10px] text-stone-400 mt-1">If any of these appear in the document header, this type gets a confidence boost (+15%)</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-stone-700 mb-1 block">Status</label>
                    <select x-model="trainingForm.status"
                            class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="trainingModal = false"
                        class="px-4 py-2 text-sm text-stone-600 bg-stone-100 rounded-lg hover:bg-stone-200 transition-colors">Cancel</button>
                <button @click="saveTraining()"
                        class="px-4 py-2 text-sm text-white bg-red-700 rounded-lg hover:bg-red-800 transition-colors">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsApp() {
    return {
        trainingModal: false,
        editTraining: null,
        trainingTypeId: null,
        trainingTypeName: '',
        trainingForm: { sample_text: '', keywords: '', title_patterns: '', status: 'active' },

        openTrainingModal(typeId, typeName, training) {
            this.trainingTypeId = typeId;
            this.trainingTypeName = typeName;
            this.editTraining = training || null;
            this.trainingForm = training
                ? { sample_text: training.sample_text, keywords: training.keywords || '', title_patterns: training.title_patterns || '', status: training.status }
                : { sample_text: '', keywords: '', title_patterns: '', status: 'active' };
            this.trainingModal = true;
        },

        saveTraining() {
            const url = this.editTraining
                ? '{{ url("document-ai/training") }}/' + this.editTraining.id
                : '{{ route("document-ai.training.store") }}';

            const data = {
                _token: '{{ csrf_token() }}',
                ...this.trainingForm,
                document_type_id: this.trainingTypeId
            };

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: (res) => {
                    if (res.success) {
                        this.trainingModal = false;
                        // Update local data and re-render
                        if (this.editTraining) {
                            const items = trainingDataMap[this.trainingTypeId] || [];
                            const idx = items.findIndex(t => t.id === this.editTraining.id);
                            if (idx !== -1) {
                                items[idx].sample_text = this.trainingForm.sample_text;
                                items[idx].keywords = this.trainingForm.keywords;
                                items[idx].status = this.trainingForm.status;
                            }
                            $('#training-list-' + this.trainingTypeId).html(getTrainingHtml(this.trainingTypeId));
                        } else {
                            location.reload();
                        }
                    }
                },
                error: (xhr) => {
                    alert(xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || 'Error saving.');
                }
            });
        }
    };
}

function toggleTrainingSection(typeId) {
    const section = $('#training-section-' + typeId);
    if (section.is(':visible')) {
        section.hide();
    } else {
        section.show();
        loadTrainingData(typeId);
    }
}

function loadTrainingData(typeId) {
    const container = $('#training-list-' + typeId);
    container.html(getTrainingHtml(typeId));
}

// Pre-render training data from server
const trainingDataMap = @json($trainingDataMap);

function getTrainingHtml(typeId) {
    const items = trainingDataMap[typeId] || [];
    if (!items.length) {
        return '<p class="text-xs text-stone-400 italic">No training data yet. Click "Add Sample" to get started.</p>';
    }

    let html = '';
    items.forEach(function(item) {
        const statusBadge = item.status === 'active'
            ? '<span class="px-2 py-0.5 bg-green-50 text-green-700 text-xs rounded-full">Active</span>'
            : '<span class="px-2 py-0.5 bg-stone-100 text-stone-500 text-xs rounded-full">Inactive</span>';

        html += '<div class="bg-white border border-stone-200 rounded-lg p-3">';
        html += '<div class="flex items-start justify-between gap-3">';
        html += '<div class="flex-1 min-w-0">';
        html += '<p class="text-xs text-stone-700 line-clamp-2">' + escapeHtml(item.sample_text.substring(0, 200)) + '</p>';
        if (item.keywords) {
            html += '<p class="text-xs text-stone-400 mt-1"><strong>Keywords:</strong> ' + escapeHtml(item.keywords) + '</p>';
        }
        if (item.title_patterns) {
            html += '<p class="text-xs text-purple-500 mt-0.5"><strong>Title Patterns:</strong> ' + escapeHtml(item.title_patterns) + '</p>';
        }
        if (item.created_at) {
            html += '<p class="text-[10px] text-stone-300 mt-1">Added: ' + item.created_at + '</p>';
        }
        html += '</div>';
        html += '<div class="flex items-center gap-2 shrink-0">';
        html += statusBadge;
        html += '<button onclick="editTraining(' + typeId + ', ' + JSON.stringify(item).replace(/"/g, '&quot;') + ')" class="p-1 text-stone-400 hover:text-blue-500 transition-colors" title="Edit">';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
        html += '</button>';
        html += '<button onclick="deleteTraining(' + item.id + ', ' + typeId + ')" class="p-1 text-stone-400 hover:text-red-500 transition-colors" title="Delete">';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
        html += '</button>';
        html += '</div></div></div>';
    });

    return html;
}

function deleteTraining(id, typeId) {
    if (!confirm('Delete this training sample?')) return;

    $.ajax({
        url: '{{ url("document-ai/training") }}/' + id,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (res.success) {
                trainingDataMap[typeId] = (trainingDataMap[typeId] || []).filter(t => t.id !== id);
                $('#training-list-' + typeId).html(getTrainingHtml(typeId));
            }
        }
    });
}

function editTraining(typeId, item) {
    const el = document.querySelector('[x-data="settingsApp()"]');
    if (el && el._x_dataStack) {
        const data = el._x_dataStack[0];
        data.trainingTypeId = typeId;
        data.trainingTypeName = 'Edit';
        data.editTraining = item;
        data.trainingForm = {
            sample_text: item.sample_text,
            keywords: item.keywords || '',
            title_patterns: item.title_patterns || '',
            status: item.status
        };
        data.trainingModal = true;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
