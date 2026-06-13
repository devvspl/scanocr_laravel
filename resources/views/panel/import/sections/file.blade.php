<div id="import-wizard">
    {{-- Step 1: Upload & Configure --}}
    <div id="step-1" class="import-step">
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            {{-- Left: Table Selection --}}
            <div class="space-y-4">
                <h4 class="font-semibold text-stone-800">Target Table</h4>
                
                <div class="flex gap-3 mb-4">
                    <button type="button" onclick="setTableMode('existing')" id="btn-existing-table" class="flex-1 px-4 py-2 border-2 border-red-700 bg-red-50 text-red-700 rounded-lg font-medium">
                        Existing Table
                    </button>
                    <button type="button" onclick="setTableMode('new')" id="btn-new-table" class="flex-1 px-4 py-2 border-2 border-stone-300 text-stone-700 rounded-lg font-medium hover:border-red-700">
                        Create New Table
                    </button>
                </div>

                {{-- Existing Table Selection --}}
                <div id="existing-table-section">
                    <label class="block text-sm font-medium text-stone-700 mb-2">Select Table</label>
                    <select id="target-table" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="">Select table...</option>
                    </select>
                    <p class="text-xs text-stone-500 mt-1">Choose an existing database table to import data into</p>
                </div>

                {{-- New Table Creation --}}
                <div id="new-table-section" class="hidden space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">New Table Name</label>
                        <input type="text" id="new-table-name" class="w-full border border-stone-300 rounded-lg px-3 py-2" placeholder="e.g., custom_imports">
                        <p class="text-xs text-stone-500 mt-1">Table will be created automatically with prefix: imp_</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-xs text-blue-800">
                            <strong>Note:</strong> The table will be created with columns matching your file headers. 
                            company_id and created_by will be added automatically.
                        </p>
                    </div>
                </div>

                {{-- Source Type --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-stone-700 mb-2">Source Type</label>
                    <select id="source-type" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="excel">Excel (.xlsx, .xls)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="sql">SQL Dump (.sql)</option>
                    </select>
                </div>
            </div>

            {{-- Right: File Upload --}}
            <div>
                <h4 class="font-semibold text-stone-800 mb-4">Upload File</h4>
                
                <div class="border-2 border-dashed border-stone-300 rounded-lg p-8 text-center">
                    <input type="file" id="file-input" class="hidden" accept=".xlsx,.xls,.csv,.sql,.txt">
                    <div id="drop-zone" class="cursor-pointer">
                        <svg class="w-12 h-12 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-stone-600 mb-2">Drag and drop your file here</p>
                        <p class="text-sm text-stone-500 mb-3">or</p>
                        <button type="button" class="bg-stone-100 hover:bg-stone-200 px-4 py-2 rounded-lg text-sm font-medium">
                            Browse Files
                        </button>
                        <p class="text-xs text-stone-500 mt-3">Maximum file size: 50MB</p>
                    </div>
                    <div id="file-info" class="hidden">
                        <svg class="w-12 h-12 mx-auto text-green-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-green-600 font-medium" id="file-name"></p>
                        <div class="flex items-center justify-center gap-3 mt-3">
                            <button type="button" id="preview-file" class="inline-flex items-center text-blue-600 text-sm hover:text-blue-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Preview
                            </button>
                            <button type="button" id="remove-file" class="inline-flex items-center text-red-600 text-sm hover:text-red-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button id="btn-next-1" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Next: Map Columns →
            </button>
        </div>
    </div>

    {{-- Step 2: Map Columns --}}
    <div id="step-2" class="import-step hidden">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-stone-800">Map Columns</h4>
            <label class="flex items-center text-sm">
                <input type="checkbox" id="has-header" checked class="mr-2">
                <span>First row contains headers</span>
            </label>
        </div>

        <div id="column-mapping" class="space-y-3 mb-6 max-h-96 overflow-y-auto">
            <!-- Column mappings will be inserted here -->
        </div>

        <div class="border-t pt-4 mt-4">
            <h4 class="font-medium mb-3">Import Options</h4>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">On Conflict</label>
                    <select id="on-conflict" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="skip">Skip duplicate rows</option>
                        <option value="update">Update existing records</option>
                        <option value="create">Create duplicates</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">Unique Key</label>
                    <select id="unique-key" class="w-full border border-stone-300 rounded-lg px-3 py-2">
                        <option value="">None</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center h-full">
                        <input type="checkbox" id="save-template" class="mr-2">
                        <span class="text-sm">Save as template for reuse</span>
                    </label>
                </div>
            </div>
            <input type="text" id="template-name" class="hidden mt-3 w-full border border-stone-300 rounded-lg px-3 py-2" placeholder="Template name...">
        </div>

        <div class="mt-6 flex justify-between">
            <button id="btn-back-2" class="border border-stone-300 px-6 py-2 rounded-lg hover:bg-stone-50">
                ← Back
            </button>
            <button id="btn-start-import" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800">
                Start Import
            </button>
        </div>
    </div>

    {{-- Step 3: Progress --}}
    <div id="step-3" class="import-step hidden">
        <h4 class="font-semibold text-stone-800 mb-4">Importing Data...</h4>
        
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span>Progress</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="w-full bg-stone-200 rounded-full h-3">
                <div id="progress-bar" class="bg-red-700 h-3 rounded-full transition-all" style="width: 0%"></div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6">
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
                <p class="text-green-800 font-medium">✓ Import completed successfully!</p>
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
let tableMode = 'existing'; // 'existing' or 'new'

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, loading tables...');
    loadTables();
    
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
        const fileInput = document.getElementById('file-input');
        fileInput.value = '';
        document.getElementById('drop-zone').classList.remove('hidden');
        document.getElementById('file-info').classList.add('hidden');
        document.getElementById('btn-next-1').disabled = true;
        uploadedFileUuid = null;
    });
    
    document.getElementById('btn-next-1').addEventListener('click', () => showStep(2));
    document.getElementById('btn-back-2').addEventListener('click', () => showStep(1));
    document.getElementById('btn-start-import').addEventListener('click', startImport);
    document.getElementById('btn-new-import').addEventListener('click', () => location.reload());
    
    document.getElementById('save-template').addEventListener('change', function() {
        document.getElementById('template-name').classList.toggle('hidden', !this.checked);
    });
    
    document.getElementById('target-table').addEventListener('change', function() {
        if (this.value && tableMode === 'existing') {
            loadTableColumns(this.value);
        }
    });
});

function setTableMode(mode) {
    tableMode = mode;
    
    document.getElementById('btn-existing-table').className = mode === 'existing'
        ? 'flex-1 px-4 py-2 border-2 border-red-700 bg-red-50 text-red-700 rounded-lg font-medium'
        : 'flex-1 px-4 py-2 border-2 border-stone-300 text-stone-700 rounded-lg font-medium hover:border-red-700';
    
    document.getElementById('btn-new-table').className = mode === 'new'
        ? 'flex-1 px-4 py-2 border-2 border-red-700 bg-red-50 text-red-700 rounded-lg font-medium'
        : 'flex-1 px-4 py-2 border-2 border-stone-300 text-stone-700 rounded-lg font-medium hover:border-red-700';
    
    document.getElementById('existing-table-section').classList.toggle('hidden', mode !== 'existing');
    document.getElementById('new-table-section').classList.toggle('hidden', mode !== 'new');
}

function loadTables() {
    fetch('{{ route("master.import.tables") }}')
        .then(r => {
            if (!r.ok) throw new Error('Failed to load tables');
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
    const fileInput = document.getElementById('file-input');
    const file = fileInput.files[0];
    if (!file) return;
    
    let tableName;
    if (tableMode === 'existing') {
        tableName = document.getElementById('target-table').value;
        if (!tableName) {
            alert('Please select a target table first');
            fileInput.value = '';
            return;
        }
    } else {
        tableName = document.getElementById('new-table-name').value;
        if (!tableName) {
            alert('Please enter a new table name');
            fileInput.value = '';
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('source_type', document.getElementById('source-type').value);
    formData.append('table_name', tableName);
    formData.append('create_table', tableMode === 'new' ? '1' : '0');
    
    fetch('{{ route("master.import.upload") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        uploadedFileUuid = data.uuid;
        uploadedFileUrl = data.file_url;
        sourceHeaders = data.headers;
        
        // For new table, target columns = source headers
        if (tableMode === 'new') {
            targetColumns = data.headers;
        } else if (tableMode === 'existing') {
            // Load columns for existing table
            const tableName = document.getElementById('target-table').value;
            if (tableName) {
                loadTableColumns(tableName);
            }
        }
        
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
    
    if (!targetColumns || targetColumns.length === 0) {
        container.innerHTML = '<p class="text-red-600">Error: Target columns not loaded. Please go back and try again.</p>';
        return;
    }

    // Add header row labels
    const headerRow = document.createElement('div');
    headerRow.className = 'flex items-center gap-3 mb-2 px-3';
    headerRow.innerHTML = `
        <div class="flex-1 text-xs font-medium text-stone-500 uppercase">Source Column</div>
        <div class="w-6"></div>
        <div class="flex-1 text-xs font-medium text-stone-500 uppercase">Target Column</div>
        <div class="flex-1 text-xs font-medium text-stone-500 uppercase">Alias Name</div>
        <div class="w-8"></div>
    `;
    container.appendChild(headerRow);
    
    sourceHeaders.forEach(header => {
        if (!header || header.trim() === '') return; // Skip empty rows
        
        const row = document.createElement('div');
        row.className = 'mapping-row flex items-center gap-3 bg-stone-50 p-3 rounded-lg';
        row.innerHTML = `
            <div class="flex-1">
                <input type="text" value="${header}" readonly class="w-full border border-stone-300 rounded-lg px-3 py-2 bg-white text-sm">
            </div>
            <div class="text-stone-500">→</div>
            <div class="flex-1">
                <select class="column-map w-full border border-stone-300 rounded-lg px-3 py-2 text-sm" data-source="${header}">
                    <option value="">Skip this column</option>
                    ${targetColumns.map(col => `<option value="${col}" ${col.toLowerCase() === header.toLowerCase() ? 'selected' : ''}>${col}</option>`).join('')}
                </select>
            </div>
            <div class="flex-1">
                <input type="text" class="column-alias w-full border border-stone-300 rounded-lg px-3 py-2 text-sm" data-source="${header}" placeholder="Alias name (optional)">
            </div>
            <div class="w-8 flex justify-center">
                <button type="button" onclick="this.closest('.mapping-row').remove()" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50" title="Remove row">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `;
        container.appendChild(row);
    });
}

function startImport() {
    const mapping = {};
    const aliases = {};
    document.querySelectorAll('.column-map').forEach(select => {
        if (select.value) {
            mapping[select.dataset.source] = select.value;
        }
    });
    document.querySelectorAll('.column-alias').forEach(input => {
        if (input.value.trim()) {
            aliases[input.dataset.source] = input.value.trim();
        }
    });
    
    const tableName = tableMode === 'existing' 
        ? document.getElementById('target-table').value
        : document.getElementById('new-table-name').value;
    
    const data = {
        uuid: uploadedFileUuid,
        source_type: document.getElementById('source-type').value,
        table_name: tableName,
        column_mapping: mapping,
        column_aliases: aliases,
        options: {
            has_header_row: document.getElementById('has-header').checked,
            on_conflict: document.getElementById('on-conflict').value,
            unique_key: document.getElementById('unique-key').value,
            create_table: tableMode === 'new'
        }
    };
    
    if (document.getElementById('save-template').checked) {
        data.template_name = document.getElementById('template-name').value;
    }
    
    console.log('Starting import with data:', data);
    
    fetch('{{ route("master.import.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => {
        console.log('Response status:', r.status);
        if (!r.ok) {
            return r.text().then(text => {
                console.error('Error response:', text);
                throw new Error('Import failed: ' + r.status);
            });
        }
        return r.json();
    })
    .then(result => {
        console.log('Import started:', result);
        currentJobId = result.job_id;
        showStep(3);
        pollJobStatus();
    })
    .catch(err => {
        console.error('Import error:', err);
        alert('Failed to start import: ' + err.message);
    });
}

function pollJobStatus() {
    pollAttempts = 0;
    const interval = setInterval(() => {
        pollAttempts++;
        
        if (pollAttempts > MAX_POLL_ATTEMPTS) {
            clearInterval(interval);
            document.getElementById('import-complete').classList.remove('hidden');
            document.getElementById('import-complete').innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800 font-medium">⚠ Import is still processing. The queue worker may not be running.</p>
                    <p class="text-yellow-700 text-sm mt-1">Run: <code class="bg-yellow-100 px-2 py-0.5 rounded">php artisan queue:work</code></p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ url('master/import/jobs') }}/${currentJobId}" class="bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-800">View Details</a>
                    <button onclick="location.reload()" class="border border-stone-300 px-6 py-2 rounded-lg hover:bg-stone-50">New Import</button>
                </div>
            `;
            return;
        }
        
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
            })
            .catch(err => {
                console.error('Poll error:', err);
                clearInterval(interval);
            });
    }, 2000);
}

let uploadedFileUrl = null;
let pollAttempts = 0;
const MAX_POLL_ATTEMPTS = 150; // Stop after 5 minutes (150 * 2 seconds)

document.getElementById('preview-file').addEventListener('click', function() {
    if (!uploadedFileUrl) {
        alert('No file uploaded yet');
        return;
    }
    const embed = `https://view.officeapps.live.com/op/embed.aspx?wdDownloadButton=True&src=${encodeURIComponent(uploadedFileUrl)}`;
    document.getElementById('preview-iframe').src = embed;
    document.getElementById('preview-modal').classList.remove('hidden');
});
</script>

{{-- Preview Modal --}}
<div id="preview-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl w-[90vw] h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-stone-200">
            <h3 class="text-lg font-semibold text-stone-800">File Preview</h3>
            <button onclick="document.getElementById('preview-modal').classList.add('hidden'); document.getElementById('preview-iframe').src = '';" class="text-stone-500 hover:text-stone-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 p-2">
            <iframe id="preview-iframe" class="w-full h-full rounded-lg border border-stone-200" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>
