<div id="import-wizard">
    {{-- Step 1: Upload File --}}
    <div id="step-1" class="import-step">
        <h3 class="text-lg font-semibold mb-4">Step 1: Upload File</h3>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-2">Target Table</label>
                <select id="target-table" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                    <option value="">Select table...</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-2">Source Type</label>
                <select id="source-type" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                    <option value="excel">Excel (.xlsx, .xls)</option>
                    <option value="csv">CSV (.csv)</option>
                    <option value="sql">SQL Dump (.sql)</option>
                </select>
            </div>
        </div>

        <div class="border-2 border-dashed border-stone-300 rounded-lg p-8 text-center">
            <input type="file" id="file-input" class="hidden" accept=".xlsx,.xls,.csv,.sql,.txt">
            <div id="drop-zone" class="cursor-pointer">
                <svg class="w-12 h-12 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-stone-600 mb-2">Drag and drop your file here, or click to browse</p>
                <p class="text-sm text-stone-500">Maximum file size: 50MB</p>
            </div>
            <div id="file-info" class="hidden">
                <p class="text-green-600 font-medium" id="file-name"></p>
                <button type="button" id="remove-file" class="text-red-600 text-sm mt-2">Remove file</button>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button id="btn-next-1" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800 disabled:opacity-50" disabled>
                Next: Map Columns
            </button>
        </div>
    </div>

    {{-- Step 2: Map Columns --}}
    <div id="step-2" class="import-step hidden">
        <h3 class="text-lg font-semibold mb-4">Step 2: Map Columns</h3>
        
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" id="has-header" checked class="mr-2">
                <span class="text-sm">First row contains headers</span>
            </label>
        </div>

        <div id="column-mapping" class="space-y-3 mb-4">
            <!-- Column mappings will be inserted here -->
        </div>

        <div class="border-t pt-4 mt-4">
            <h4 class="font-medium mb-3">Import Options</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">On Conflict</label>
                    <select id="on-conflict" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="skip">Skip duplicate rows</option>
                        <option value="update">Update existing records</option>
                        <option value="create">Create duplicates</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">Unique Key (for deduplication)</label>
                    <select id="unique-key" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="">None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="flex items-center">
                <input type="checkbox" id="save-template" class="mr-2">
                <span class="text-sm">Save as template</span>
            </label>
            <input type="text" id="template-name" class="hidden mt-2 w-full border border-stone-300 rounded-lg px-3 py-2" placeholder="Template name...">
        </div>

        <div class="mt-4 flex justify-between">
            <button id="btn-back-2" class="border border-stone-300 px-6 py-2 rounded-lg hover:bg-stone-50">
                Back
            </button>
            <button id="btn-start-import" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800">
                Start Import
            </button>
        </div>
    </div>

    {{-- Step 3: Progress --}}
    <div id="step-3" class="import-step hidden">
        <h3 class="text-lg font-semibold mb-4">Step 3: Importing...</h3>
        
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-2">
                <span>Progress</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="w-full bg-stone-200 rounded-full h-3">
                <div id="progress-bar" class="bg-red-700 h-3 rounded-full transition-all" style="width: 0%"></div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="text-center p-4 bg-stone-50 rounded-lg">
                <div class="text-2xl font-bold text-stone-700" id="stat-total">0</div>
                <div class="text-sm text-stone-500">Total Rows</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-700" id="stat-success">0</div>
                <div class="text-sm text-green-600">Success</div>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-2xl font-bold text-red-700" id="stat-failed">0</div>
                <div class="text-sm text-red-600">Failed</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-2xl font-bold text-yellow-700" id="stat-skipped">0</div>
                <div class="text-sm text-yellow-600">Skipped</div>
            </div>
        </div>

        <div id="import-complete" class="hidden">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-green-800 font-medium">Import completed!</p>
            </div>
            <div class="flex gap-3">
                <a id="view-details-link" href="#" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800">
                    View Details
                </a>
                <button id="btn-new-import" class="border border-stone-300 px-6 py-2 rounded-lg hover:bg-stone-50">
                    New Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let uploadedFileUuid = null;
let sourceHeaders = [];
let targetColumns = [];
let currentJobId = null;

// Load tables on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, loading tables...');
    loadTables();
    
    // File input handling
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    
    dropZone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', handleFileSelect);
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-red-500');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-red-500');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-red-500');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect();
    });
    
    document.getElementById('remove-file').addEventListener('click', () => {
        fileInput.value = '';
        document.getElementById('drop-zone').classList.remove('hidden');
        document.getElementById('file-info').classList.add('hidden');
        document.getElementById('btn-next-1').disabled = true;
        uploadedFileUuid = null;
    });
    
    document.getElementById('btn-next-1').addEventListener('click', () => showStep(2));
    document.getElementById('btn-back-2').addEventListener('click', () => showStep(1));
    document.getElementById('btn-start-import').addEventListener('click', startImport);
    document.getElementById('btn-new-import').addEventListener('click', () => {
        location.reload();
    });
    
    document.getElementById('save-template').addEventListener('change', function() {
        document.getElementById('template-name').classList.toggle('hidden', !this.checked);
    });
    
    document.getElementById('target-table').addEventListener('change', function() {
        if (this.value) {
            loadTableColumns(this.value);
        }
    });
});

function loadTables() {
    fetch('{{ route("master.import.tables") }}')
        .then(r => {
            if (!r.ok) {
                throw new Error('Failed to load tables: ' + r.statusText);
            }
            return r.json();
        })
        .then(data => {
            console.log('Tables loaded:', data);
            const select = document.getElementById('target-table');
            
            if (!data.tables || data.tables.length === 0) {
                console.error('No tables found');
                return;
            }
            
            data.tables.forEach(table => {
                const option = document.createElement('option');
                option.value = table;
                option.textContent = table;
                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Error loading tables:', err);
            alert('Failed to load tables: ' + err.message);
        });
}

function loadTableColumns(table) {
    fetch(`{{ route("master.import.table-columns") }}?table=${table}`)
        .then(r => r.json())
        .then(data => {
            targetColumns = data.columns;
            const uniqueKeySelect = document.getElementById('unique-key');
            uniqueKeySelect.innerHTML = '<option value="">None</option>';
            data.columns.forEach(col => {
                const option = document.createElement('option');
                option.value = col;
                option.textContent = col;
                uniqueKeySelect.appendChild(option);
            });
        });
}

function handleFileSelect() {
    const file = fileInput.files[0];
    if (!file) return;
    
    const table = document.getElementById('target-table').value;
    if (!table) {
        alert('Please select a target table first');
        fileInput.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('source_type', document.getElementById('source-type').value);
    formData.append('table_name', table);
    
    fetch('{{ route("master.import.upload") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        uploadedFileUuid = data.uuid;
        sourceHeaders = data.headers;
        
        document.getElementById('drop-zone').classList.add('hidden');
        document.getElementById('file-info').classList.remove('hidden');
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('btn-next-1').disabled = false;
    })
    .catch(err => {
        alert('Upload failed: ' + err.message);
    });
}

function showStep(step) {
    document.querySelectorAll('.import-step').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step-${step}`).classList.remove('hidden');
    
    if (step === 2) {
        renderColumnMapping();
    }
}

function renderColumnMapping() {
    const container = document.getElementById('column-mapping');
    container.innerHTML = '';
    
    sourceHeaders.forEach(header => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-4';
        row.innerHTML = `
            <div class="flex-1">
                <input type="text" value="${header}" readonly class="w-full border border-stone-300 rounded-lg px-3 py-2 bg-stone-50">
            </div>
            <div class="text-stone-500">→</div>
            <div class="flex-1">
                <select class="column-map w-full border border-stone-300 rounded-lg px-3 py-2" data-source="${header}">
                    <option value="">Skip this column</option>
                    ${targetColumns.map(col => `<option value="${col}" ${col.toLowerCase() === header.toLowerCase() ? 'selected' : ''}>${col}</option>`).join('')}
                </select>
            </div>
        `;
        container.appendChild(row);
    });
}

function startImport() {
    const mapping = {};
    document.querySelectorAll('.column-map').forEach(select => {
        if (select.value) {
            mapping[select.dataset.source] = select.value;
        }
    });
    
    const data = {
        uuid: uploadedFileUuid,
        source_type: document.getElementById('source-type').value,
        table_name: document.getElementById('target-table').value,
        column_mapping: mapping,
        options: {
            has_header_row: document.getElementById('has-header').checked,
            on_conflict: document.getElementById('on-conflict').value,
            unique_key: document.getElementById('unique-key').value
        }
    };
    
    if (document.getElementById('save-template').checked) {
        data.template_name = document.getElementById('template-name').value;
    }
    
    fetch('{{ route("master.import.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        currentJobId = result.job_id;
        showStep(3);
        pollJobStatus();
    });
}

function pollJobStatus() {
    const interval = setInterval(() => {
        fetch(`{{ url('master/import/status') }}/${currentJobId}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('progress-bar').style.width = data.progress + '%';
                document.getElementById('progress-text').textContent = data.progress + '%';
                document.getElementById('stat-total').textContent = data.total_rows;
                document.getElementById('stat-success').textContent = data.success_rows;
                document.getElementById('stat-failed').textContent = data.failed_rows;
                document.getElementById('stat-skipped').textContent = data.skipped_rows;
                
                if (data.status === 'completed' || data.status === 'failed' || data.status === 'partial') {
                    clearInterval(interval);
                    document.getElementById('import-complete').classList.remove('hidden');
                    document.getElementById('view-details-link').href = `{{ url('master/import/jobs') }}/${currentJobId}`;
                }
            });
    }, 2000);
}
</script>
