<div>
    <h3 class="text-lg font-semibold mb-4">Saved Templates</h3>
    
    <div id="templates-list" class="space-y-3">
        <!-- Templates will be loaded here -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadTemplates();
});

function loadTemplates() {
    fetch('{{ route("master.import.templates") }}')
        .then(r => r.json())
        .then(templates => {
            const container = document.getElementById('templates-list');
            
            if (templates.length === 0) {
                container.innerHTML = '<p class="text-stone-500 text-center py-8">No templates saved yet</p>';
                return;
            }
            
            container.innerHTML = templates.map(template => `
                <div class="border border-stone-200 rounded-lg p-4 flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-stone-900">${template.name}</h4>
                        <div class="flex gap-3 mt-1 text-sm text-stone-500">
                            <span>Table: ${template.data_type}</span>
                            <span>•</span>
                            <span>Source: ${template.source_type.toUpperCase()}</span>
                            <span>•</span>
                            <span>Created: ${new Date(template.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="useTemplate(${template.id})" class="bg-red-700 text-white px-4 py-2 rounded-lg hover:bg-red-800 text-sm">
                            Use Template
                        </button>
                        <button onclick="deleteTemplate(${template.id})" class="border border-red-300 text-red-700 px-4 py-2 rounded-lg hover:bg-red-50 text-sm">
                            Delete
                        </button>
                    </div>
                </div>
            `).join('');
        });
}

function useTemplate(id) {
    // Redirect to file import tab with template pre-selected
    window.location.href = '{{ route("master.import.index") }}?tab=file&template=' + id;
}

function deleteTemplate(id) {
    if (!confirm('Are you sure you want to delete this template?')) return;
    
    fetch(`{{ url('master/import/templates') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(() => {
        loadTemplates();
    });
}
</script>
