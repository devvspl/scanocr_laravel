<div class="overflow-x-auto">
    {{-- Header with Bulk Actions --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-stone-800">Recent Imports</h3>
        
        <div class="flex items-center gap-3">
            <div id="selected-count" class="text-sm text-stone-600 font-medium hidden">
                <span id="selected-count-text">0</span> selected
            </div>
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" id="select-all-imports" class="custom-checkbox mr-2">
                <span class="text-sm text-stone-600 font-medium">Select All</span>
            </label>
            <button onclick="bulkDeleteImports()" id="bulk-delete-btn" class="hidden bg-red-600 text-white px-4 py-1.5 rounded-lg hover:bg-red-700 text-sm font-medium transition-colors">
                Delete Selected
            </button>
        </div>
    </div>

    <table class="w-full">
        <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
                <th class="px-4 py-3 text-left w-10">
                    #
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Sr No.</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Table</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Source</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rows</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Started</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200">
            @forelse($jobs->take(10) as $job)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3">
                    <input type="checkbox" class="import-checkbox custom-checkbox" data-id="{{ $job->id }}">
                </td>
                <td class="px-4 py-3 text-sm">{{ $job->id }}</td>
                <td class="px-4 py-3 text-sm font-medium">{{ $job->data_type }}</td>
                <td class="px-4 py-3 text-sm">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                        {{ strtoupper($job->source_type) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if($job->status === 'completed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Completed
                        </span>
                    @elseif($job->status === 'failed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Failed
                        </span>
                    @elseif($job->status === 'partial')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            Partial
                        </span>
                    @elseif($job->status === 'processing')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            Processing
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                            Pending
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm">
                    <div class="flex gap-2 text-xs">
                        <span class="text-green-600">✓ {{ $job->success_rows }}</span>
                        <span class="text-red-600">✗ {{ $job->failed_rows }}</span>
                        <span class="text-yellow-600">⊘ {{ $job->skipped_rows }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-stone-500">
                    {{ $job->started_at ? $job->started_at->format('M d, H:i') : '-' }}
                </td>
                <td class="px-4 py-3 text-sm">
                    <div class="flex gap-2">
                        <a href="{{ route('master.import.show', $job) }}" class="text-red-700 hover:text-red-800 font-medium">
                            View
                        </a>
                        <button onclick="deleteImport({{ $job->id }})" class="text-red-600 hover:text-red-800 font-medium">
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-stone-500">
                    No import history yet. Start your first import above!
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($jobs->count() > 10)
<div class="mt-4 text-center">
    <a href="{{ route('master.import.index') }}?view=all" class="text-red-700 hover:text-red-800 font-medium">
        View All Imports →
    </a>
</div>
@endif

<script>
// Select all checkbox
document.getElementById('select-all-imports')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.import-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Individual checkboxes
document.querySelectorAll('.import-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checked = document.querySelectorAll('.import-checkbox:checked');
    const bulkBtn = document.getElementById('bulk-delete-btn');
    const countDiv = document.getElementById('selected-count');
    const countText = document.getElementById('selected-count-text');
    
    if (checked.length > 0) {
        bulkBtn.classList.remove('hidden');
        countDiv.classList.remove('hidden');
        countText.textContent = checked.length;
    } else {
        bulkBtn.classList.add('hidden');
        countDiv.classList.add('hidden');
    }
}

function bulkDeleteImports() {
    const checked = document.querySelectorAll('.import-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.dataset.id);
    
    if (ids.length === 0) {
        alert('Please select imports to delete');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${ids.length} import job(s)? This will also delete all associated row data.`)) {
        return;
    }
    
    // Delete each import
    Promise.all(ids.map(id => 
        fetch(`{{ url('master/import/jobs') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
    ))
    .then(() => {
        alert('Selected imports deleted successfully');
        location.reload();
    })
    .catch(err => {
        alert('Failed to delete some imports: ' + err.message);
        location.reload();
    });
}

function deleteImport(jobId) {
    if (!confirm('Are you sure you want to delete this import job? This will also delete all associated row data.')) {
        return;
    }
    
    fetch(`{{ url('master/import/jobs') }}/${jobId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'Import deleted successfully');
        location.reload();
    })
    .catch(err => {
        alert('Failed to delete import: ' + err.message);
    });
}
</script>

<style>
/* Custom checkbox styling with red theme */
.custom-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 1.125rem;
    height: 1.125rem;
    border: 2px solid #d6d3d1;
    border-radius: 0.25rem;
    background-color: white;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.custom-checkbox:hover {
    border-color: #b91c1c;
}

.custom-checkbox:checked {
    background-color: #b91c1c;
    border-color: #b91c1c;
}

.custom-checkbox:checked::after {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0.0625rem;
    width: 0.375rem;
    height: 0.625rem;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.custom-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.1);
}

.custom-checkbox:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
