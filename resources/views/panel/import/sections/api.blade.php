<div class="max-w-xl space-y-5">

    <h4 class="font-semibold text-stone-800">API Import</h4>
    <p class="text-sm text-stone-500 -mt-3">Fetch data from a REST API endpoint and import it into a table.</p>

    {{-- Name --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Connection Name</label>
        <input type="text" id="api-name"
               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
               placeholder="e.g., Tally Sync">
    </div>

    {{-- URL --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">API URL</label>
        <input type="url" id="api-url"
               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
               placeholder="https://api.example.com/v1/data">
    </div>

    {{-- Auth --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Authentication</label>
        <select id="api-auth-type"
                class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
                onchange="toggleApiAuth()">
            <option value="none">None</option>
            <option value="api_key">API Key</option>
            <option value="bearer">Bearer Token</option>
            <option value="basic">Basic Auth (Username / Password)</option>
        </select>
    </div>

    {{-- API Key fields --}}
    <div id="auth-api-key" class="hidden grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Header Name</label>
            <input type="text" id="api-key-header"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
                   placeholder="X-API-Key" value="X-API-Key">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Key Value</label>
            <input type="text" id="api-key-value"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
                   placeholder="Your API key">
        </div>
    </div>

    {{-- Bearer Token --}}
    <div id="auth-bearer" class="hidden">
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Bearer Token</label>
        <input type="text" id="bearer-token"
               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
               placeholder="Your token">
    </div>

    {{-- Basic Auth --}}
    <div id="auth-basic" class="hidden grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Username</label>
            <input type="text" id="basic-username"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Password</label>
            <input type="password" id="basic-password"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
        </div>
    </div>

    {{-- Data path --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">
            Data Path
            <span class="font-normal text-stone-400 ml-1">(dot notation — leave empty if root is the array)</span>
        </label>
        <input type="text" id="api-data-path"
               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10"
               placeholder="e.g., data.items">
    </div>

    {{-- Target table --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Target Table</label>
        <select id="api-target-table"
                class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
            <option value="">Select table...</option>
        </select>
    </div>

    {{-- Test result --}}
    <div id="api-test-result" class="hidden rounded-lg border px-4 py-3 text-sm"></div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 pt-1">
        <button onclick="testApiImport()"
                class="flex-1 border border-stone-300 rounded-lg px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 transition-colors">
            Test Connection
        </button>
        <button onclick="saveApiImport()"
                class="flex-1 bg-red-700 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-red-800 transition-colors">
            Save &amp; Import
        </button>
    </div>

</div>

<script>
function toggleApiAuth() {
    const type = document.getElementById('api-auth-type').value;
    ['auth-api-key', 'auth-bearer', 'auth-basic'].forEach(id =>
        document.getElementById(id).classList.add('hidden')
    );
    if (type === 'api_key')  document.getElementById('auth-api-key').classList.remove('hidden');
    if (type === 'bearer')   document.getElementById('auth-bearer').classList.remove('hidden');
    if (type === 'basic')    document.getElementById('auth-basic').classList.remove('hidden');
}

function buildApiPayload() {
    const authType = document.getElementById('api-auth-type').value;
    const authConfig = {};
    if (authType === 'api_key') {
        authConfig.header_name = document.getElementById('api-key-header').value;
        authConfig.api_key     = document.getElementById('api-key-value').value;
    } else if (authType === 'bearer') {
        authConfig.token = document.getElementById('bearer-token').value;
    } else if (authType === 'basic') {
        authConfig.username = document.getElementById('basic-username').value;
        authConfig.password = document.getElementById('basic-password').value;
    }

    return {
        name:            document.getElementById('api-name').value,
        provider:        'rest',
        api_type:        'rest',
        http_method:     'GET',
        base_url:        document.getElementById('api-url').value,
        endpoint:        document.getElementById('api-url').value,
        auth_type:       authType,
        auth_config:     authConfig,
        headers:         {},
        query_params:    {},
        request_body:    null,
        response_format: 'json',
        data_path:       document.getElementById('api-data-path').value,
        pagination_type: 'none',
        pagination_config: {},
        timeout:         30,
        verify_ssl:      true,
        data_type:       document.getElementById('api-target-table').value,
        target_table:    document.getElementById('api-target-table').value,
        create_table:    false,
        field_mapping:   {},
        sync_frequency:  'manual',
    };
}

function showApiResult(success, message) {
    const el = document.getElementById('api-test-result');
    el.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-800',
                                   'border-red-200',   'bg-red-50',   'text-red-800');
    if (success) {
        el.classList.add('border-green-200', 'bg-green-50', 'text-green-800');
    } else {
        el.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
    }
    el.textContent = message;
}

function testApiImport() {
    const url = document.getElementById('api-url').value.trim();
    if (!url) { showApiResult(false, 'Please enter an API URL.'); return; }

    showApiResult(true, 'Testing connection…');

    fetch(url, { method: 'GET', signal: AbortSignal.timeout(10000) })
        .then(r => {
            if (r.ok) {
                showApiResult(true, `✓ Connection successful (HTTP ${r.status}). Ready to import.`);
            } else {
                showApiResult(false, `Connection returned HTTP ${r.status}.`);
            }
        })
        .catch(err => showApiResult(false, 'Connection failed: ' + err.message));
}

function saveApiImport() {
    const data = buildApiPayload();
    if (!data.name)         { showApiResult(false, 'Please enter a connection name.'); return; }
    if (!data.base_url)     { showApiResult(false, 'Please enter an API URL.'); return; }
    if (!data.target_table) { showApiResult(false, 'Please select a target table.'); return; }

    fetch('{{ route("master.import.api-connections.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showApiResult(true, '✓ ' + (result.message || 'API connection saved successfully.'));
        } else {
            showApiResult(false, result.message || 'Failed to save API connection.');
        }
    })
    .catch(err => showApiResult(false, 'Request failed: ' + err.message));
}

// Load tables on mount
document.addEventListener('DOMContentLoaded', function () {
    fetch('{{ route("master.import.tables") }}')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('api-target-table');
            (data.tables || []).forEach(table => {
                const opt = document.createElement('option');
                opt.value = table;
                opt.textContent = table;
                select.appendChild(opt);
            });
        });
});
</script>
