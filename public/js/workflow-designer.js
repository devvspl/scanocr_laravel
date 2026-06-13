/* ═══════════════════════════════════════════════════════════════════════════
   WORKFLOW DESIGNER — Main JavaScript
   ═══════════════════════════════════════════════════════════════════════════ */

const WFDesigner = {
    activeStageId: null,
    activeTab: 'actions',
    saveTimer: null,

    init() {
        this.renderStageLibrary();
        this.renderPipeline();
        this.initSortable();
        this.bindEvents();
    },

    // ── STAGE LIBRARY ────────────────────────────────────────────────────────

    renderStageLibrary() {
        const container = document.getElementById('stage-library');
        container.innerHTML = WF_DATA.stages.map(stage => `
            <div class="wf-library-item ${stage.is_active ? '' : 'is-inactive'}" data-stage-id="${stage.id}">
                <div class="wf-library-icon" style="background: ${stage.color}">
                    <i class="${stage.icon}"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 13px; font-weight: 600; color: #292524;">${stage.display_name}</div>
                    <div style="font-size: 11px; color: #78716c;">${stage.actions.length} actions</div>
                </div>
                <div class="wf-library-toggle ${stage.is_active ? 'on' : ''}" data-stage-id="${stage.id}"></div>
            </div>
        `).join('');

        // Bind toggle events
        container.querySelectorAll('.wf-library-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const stageId = parseInt(toggle.dataset.stageId);
                this.toggleStageActive(stageId);
            });
        });
    },

    // ── PIPELINE CANVAS ──────────────────────────────────────────────────────

    renderPipeline() {
        const canvas = document.getElementById('wf-pipeline-canvas');
        const sortedStages = [...WF_DATA.stages].sort((a, b) => a.position - b.position);

        canvas.innerHTML = sortedStages.map((stage, i) => `
            <div class="wf-stage-card ${stage.is_active ? '' : 'is-inactive'} ${stage.is_optional ? 'is-optional' : ''}"
                 data-stage-id="${stage.id}"
                 data-system-key="${stage.system_key}">
                <div class="wf-card-header" style="background: ${stage.color}">
                    <span class="wf-drag-handle">⠿</span>
                    <i class="${stage.icon}"></i>
                    <input type="text" class="stage-name-input" value="${stage.display_name}" data-stage-id="${stage.id}" style="background: transparent; border: none; color: white; font-weight: 600; font-size: 13px; flex: 1; outline: none; padding: 2px 4px; border-radius: 4px;" />
                    <span style="margin-left: auto; font-size: 11px; opacity: 0.8;">#${stage.position}</span>
                    ${stage.is_optional ? '<span style="font-size: 10px; background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 4px; margin-left: 6px;">OPTIONAL</span>' : ''}
                    <button class="btn-delete-stage" data-stage-id="${stage.id}" title="Delete Stage" style="margin-left: 6px; width: 20px; height: 20px; display: flex; align-items: center; justify-center; background: rgba(255,255,255,0.2); border-radius: 4px; border: none; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;">
                        <svg class="w-3 h-3" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
                <div class="wf-card-body">
                    <span>${stage.actions.length} Actions</span>
                    ${stage.page_id ? `<span>•</span><span style="color: #b91c1c; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>Form</span>` : ''}
                </div>
                ${(stage.sub_stages && stage.sub_stages.length > 0) ? `
                    <div style="padding: 0 16px 12px; border-top: 1px solid #f5f5f4; margin-top: 4px;">
                        <div style="padding-top: 8px;">
                            ${stage.sub_stages.map((sub, si) => `
                                <div class="sub-stage-card" data-sub-stage-id="${sub.id}" data-parent-stage-id="${stage.id}" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; margin-top: ${si > 0 ? '6px' : '0'}; background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 8px; cursor: pointer; transition: all 0.15s; position: relative;" onmouseover="this.style.borderColor='#6366f1'; this.style.background='#eef2ff';" onmouseout="this.style.borderColor='#e7e5e4'; this.style.background='#fafaf9';">
                                    <div style="width: 12px; display: flex; flex-direction: column; align-items: center; position: absolute; left: -18px; top: 50%; transform: translateY(-50%);">
                                        <div style="width: 12px; height: 1px; background: #c7d2fe;"></div>
                                    </div>
                                    <div style="width: 24px; height: 24px; border-radius: 6px; background: ${sub.color || stage.color}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="${sub.icon || 'fa-solid fa-circle-dot'}" style="color: white; font-size: 10px;"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="sub-stage-name" data-sub-id="${sub.id}" data-parent-id="${stage.id}" style="font-size: 11px; font-weight: 600; color: #292524; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: text; padding: 1px 4px; border-radius: 3px; border: 1px solid transparent;" onclick="event.stopPropagation(); this.contentEditable=true; this.focus(); this.style.borderColor='#6366f1'; this.style.background='#fff';" onblur="this.contentEditable=false; this.style.borderColor='transparent'; this.style.background='transparent'; WFDesigner.updateSubStageName(parseInt(this.dataset.subId), this.textContent.trim(), parseInt(this.dataset.parentId));" onkeydown="if(event.key==='Enter'){event.preventDefault(); this.blur();}">${sub.display_name}</div>
                                        <div style="font-size: 9px; color: #a8a29e;">${sub.actions ? sub.actions.length + ' actions' : '0 actions'}${sub.page_id ? ' • Form' : ''}</div>
                                    </div>
                                    <button class="sub-stage-config" data-sub-stage-id="${sub.id}" data-parent-stage-id="${stage.id}" style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: #a8a29e; transition: all 0.15s;" onmouseover="this.style.background='#e0e7ff'; this.style.color='#4f46e5';" onmouseout="this.style.background='transparent'; this.style.color='#a8a29e';">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09"/></svg>
                                    </button>
                                    <button class="sub-stage-delete" data-sub-id="${sub.id}" style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: #d6d3d1; transition: all 0.15s;" onmouseover="this.style.background='#fee2e2'; this.style.color='#dc2626';" onmouseout="this.style.background='transparent'; this.style.color='#d6d3d1';">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                <div class="wf-card-footer">
                    <div class="wf-toggle ${stage.is_active ? 'on' : ''}" data-stage-id="${stage.id}"></div>
                    <button class="btn-add-substage" data-stage-id="${stage.id}" style="font-size: 11px; padding: 5px 10px; background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;" onmouseover="this.style.background='#c7d2fe'" onmouseout="this.style.background='#eef2ff'"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Sub-Stage</button>
                    <button class="btn-config" data-stage-id="${stage.id}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Configure</button>
                </div>
            </div>
            ${i < sortedStages.length - 1 ? `<div class="wf-arrow ${sortedStages[i + 1].is_optional ? 'optional' : ''}"><span style="display: none;">↓</span></div>` : ''}
        `).join('');

        // Bind events
        this.bindPipelineEvents();
    },

    bindPipelineEvents() {
        const canvas = document.getElementById('wf-pipeline-canvas');
        
        canvas.querySelectorAll('.btn-config').forEach(btn => {
            btn.addEventListener('click', () => {
                const stageId = parseInt(btn.dataset.stageId);
                this.openDrawer(stageId);
            });
        });

        canvas.querySelectorAll('.wf-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const stageId = parseInt(toggle.dataset.stageId);
                this.toggleStageActive(stageId);
            });
        });

        // Bind delete stage events
        canvas.querySelectorAll('.btn-delete-stage').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const stageId = parseInt(btn.dataset.stageId);
                this.deleteStage(stageId);
            });
            btn.addEventListener('mouseenter', (e) => {
                e.target.closest('button').style.opacity = '1';
            });
            btn.addEventListener('mouseleave', (e) => {
                e.target.closest('button').style.opacity = '0.7';
            });
        });

        // Bind add sub-stage buttons
        canvas.querySelectorAll('.btn-add-substage').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const parentId = parseInt(btn.dataset.stageId);
                this.showAddSubStageModal(parentId);
            });
        });

        // Bind sub-stage pill click (configure)
        canvas.querySelectorAll('.sub-stage-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.sub-stage-delete') || e.target.closest('.sub-stage-config')) return;
                const subStageId = parseInt(card.dataset.subStageId);
                const parentId = parseInt(card.dataset.parentStageId);
                const parent = WF_DATA.stages.find(s => s.id === parentId);
                if (parent && parent.sub_stages) {
                    const subStage = parent.sub_stages.find(s => s.id === subStageId);
                    if (subStage) this.openDrawer(subStageId, subStage, parentId);
                }
            });
        });

        // Bind sub-stage configure button
        canvas.querySelectorAll('.sub-stage-config').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const subStageId = parseInt(btn.dataset.subStageId);
                const parentId = parseInt(btn.dataset.parentStageId);
                const parent = WF_DATA.stages.find(s => s.id === parentId);
                if (parent && parent.sub_stages) {
                    const subStage = parent.sub_stages.find(s => s.id === subStageId);
                    if (subStage) this.openDrawer(subStageId, subStage, parentId);
                }
            });
        });

        // Bind sub-stage delete
        canvas.querySelectorAll('.sub-stage-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const subId = parseInt(btn.dataset.subId);
                this.deleteSubStage(subId);
            });
        });

        // Bind stage name edit events
        canvas.querySelectorAll('.stage-name-input').forEach(input => {
            input.addEventListener('focus', (e) => {
                e.target.style.background = 'rgba(255,255,255,0.2)';
            });
            input.addEventListener('blur', (e) => {
                e.target.style.background = 'transparent';
                const stageId = parseInt(e.target.dataset.stageId);
                const newName = e.target.value.trim();
                if (newName) {
                    this.updateStageName(stageId, newName);
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.target.blur();
                }
            });
        });
    },
    // ── SORTABLE (Drag & Drop) ───────────────────────────────────────────────

    initSortable() {
        Sortable.create(document.getElementById('wf-pipeline-canvas'), {
            animation: 150,
            handle: '.wf-drag-handle',
            filter: '.wf-arrow',
            onEnd: (evt) => {
                const order = [];
                document.querySelectorAll('.wf-stage-card').forEach((el, i) => {
                    order.push({ id: parseInt(el.dataset.stageId), position: i + 1 });
                });
                this.saveOrder(order);
            }
        });
    },

    async saveOrder(order) {
        const url = WF_DATA.routes.stageReorder;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ order }),
            });
            const json = await res.json();
            if (json.success) {
                // Update local data
                order.forEach(item => {
                    const stage = WF_DATA.stages.find(s => s.id === item.id);
                    if (stage) stage.position = item.position;
                });
                this.showSaveStatus('saved');
                this.renderPipeline();
            }
        } catch (e) {
            this.showSaveStatus('error');
        }
    },

    // ── DRAWER ───────────────────────────────────────────────────────────────

    openDrawer(stageId, subStageObj = null, parentId = null) {
        this.activeStageId = stageId;
        let stage;
        if (subStageObj) {
            stage = subStageObj;
            this._activeSubStageParentId = parentId;
        } else {
            stage = WF_DATA.stages.find(s => s.id === stageId);
            this._activeSubStageParentId = null;
        }
        if (!stage) return;

        document.getElementById('drawer-stage-name').textContent = stage.display_name + (subStageObj ? ' (sub-stage)' : '');
        document.getElementById('wf-drawer').classList.add('open');

        document.querySelectorAll('.wf-stage-card').forEach(card => {
            card.classList.toggle('wf-card-selected', parseInt(card.dataset.stageId) === (parentId || stageId));
        });

        this.renderDrawerTab(this.activeTab, stage);
    },

    closeDrawer() {
        document.getElementById('wf-drawer').classList.remove('open');
        document.querySelectorAll('.wf-stage-card').forEach(card => card.classList.remove('wf-card-selected'));
        this.activeStageId = null;
    },
    renderDrawerTab(tab, stage) {
        this.activeTab = tab;

        // Update tab active state
        document.querySelectorAll('#drawer-tabs a').forEach(a => {
            a.classList.toggle('active', a.dataset.tab === tab);
        });

        const content = document.getElementById('drawer-content');

        if (tab === 'actions') {
            content.innerHTML = this.renderActionsTab(stage);
            this.bindActionEvents(stage);
        } else if (tab === 'layout') {
            content.innerHTML = this.renderLayoutTab(stage);
            this.bindLayoutEvents(stage);
        } else if (tab === 'config') {
            content.innerHTML = this.renderConfigTab(stage);
            this.bindConfigEvents(stage);
        }
    },

    // ── CONFIG TAB ───────────────────────────────────────────────────────────

    renderConfigTab(stage) {
        // Ensure config is always a plain object, never an array
        const config = (stage.config && !Array.isArray(stage.config)) ? stage.config : {};

        // Common stage settings
        const commonSettings = `
            <div class="wf-config-group">
                <label class="wf-config-label">Linked Form (Page Builder)</label>
                <select class="wf-config-select" data-stage-prop="page_id">
                    <option value="">— No Form Linked —</option>
                    ${(WF_DATA.allPages || []).map(p => `<option value="${p.id}" ${stage.page_id == p.id ? 'selected' : ''}>${p.name}</option>`).join('')}
                </select>
                ${stage.page_id ? `<a href="/master/page-builder/${stage.page_id}/edit" target="_blank" class="text-xs text-red-700 hover:underline mt-1 inline-block">✎ Edit Form in Page Builder</a>` : ''}
            </div>
            <div id="form-fields-preview" class="mb-3"></div>
            <hr class="my-3 border-stone-200">
            <div class="wf-config-group">
                <label class="wf-config-label">Stage Icon Class</label>
                <input type="text" class="wf-config-input" value="${stage.icon}" data-stage-prop="icon" placeholder="fa-solid fa-circle">
            </div>
            <div class="wf-config-group">
                <label class="wf-config-label">Stage Color</label>
                <input type="color" class="wf-config-input" value="${stage.color}" data-stage-prop="color" style="height: 40px;">
            </div>
            <div class="wf-config-group">
                <label class="wf-config-label">
                    <input type="checkbox" ${stage.is_optional ? 'checked' : ''} data-stage-prop="is_optional">
                    Optional Stage
                </label>
            </div>
            <hr class="my-4 border-stone-200">
        `;

        // Dynamic configuration based on stage type
        let specificConfig = this.getStageSpecificConfig(stage.system_key, config);

        // Load form fields preview if page is linked
        if (stage.page_id) {
            setTimeout(() => this.loadFormFieldsPreview(stage.page_id), 100);
        }

        return commonSettings + specificConfig;
    },

    getStageSpecificConfig(systemKey, config) {
        // Ensure config is always a plain object
        if (!config || Array.isArray(config)) config = {};

        // File Upload & Storage Configuration
        if (systemKey.includes('upload') || systemKey.includes('scan') || systemKey.includes('file')) {
            return `
                <h4 class="text-sm font-bold text-stone-700 mb-3">File Upload Configuration</h4>
                <div class="wf-config-group">
                    <label class="wf-config-label">Storage Type</label>
                    <select class="wf-config-select" data-config="storage_type">
                        <option value="local" ${config.storage_type === 'local' ? 'selected' : ''}>Local Storage</option>
                        <option value="s3" ${config.storage_type === 's3' ? 'selected' : ''}>Amazon S3</option>
                        <option value="azure" ${config.storage_type === 'azure' ? 'selected' : ''}>Azure Blob</option>
                    </select>
                </div>
                <div class="wf-config-group">
                    <label class="wf-config-label">Max File Size (MB)</label>
                    <input type="number" class="wf-config-input" value="${config.max_file_size_mb || 10}" data-config="max_file_size_mb">
                </div>
                <div class="wf-config-group">
                    <label class="wf-config-label">Allowed Extensions</label>
                    <input type="text" class="wf-config-input" value="${Array.isArray(config.allowed_extensions) ? config.allowed_extensions.join(', ') : (config.allowed_extensions || 'pdf, jpg, png')}" data-config="allowed_extensions">
                </div>
            `;
        }

        // API Integration Configuration
        if (systemKey.includes('api') || systemKey.includes('extraction')) {
            return `
                <h4 class="text-sm font-bold text-stone-700 mb-3">API Integration</h4>
                <div class="wf-config-group">
                    <label class="wf-config-label">API Endpoint</label>
                    <input type="url" class="wf-config-input" value="${config.api_endpoint || ''}" data-config="api_endpoint">
                </div>
                <div class="wf-config-group">
                    <label class="wf-config-label">API Key</label>
                    <input type="password" class="wf-config-input" value="${config.api_key || ''}" data-config="api_key">
                </div>
                <button class="tb-btn tb-btn-edit w-full mt-2" id="btn-test-api">Test Connection</button>
            `;
        }

        // Default configuration
        return `
            <h4 class="text-sm font-bold text-stone-700 mb-3">General Configuration</h4>
            <div class="wf-config-group">
                <label class="wf-config-label">Description</label>
                <textarea class="wf-config-input" rows="3" data-config="description">${config.description || ''}</textarea>
            </div>
            <div class="wf-config-group">
                <label class="wf-config-label">
                    <input type="checkbox" ${config.auto_advance ? 'checked' : ''} data-config="auto_advance">
                    Auto-advance to next stage
                </label>
            </div>
        `;
    },

    async loadFormFieldsPreview(pageId) {
        const container = document.getElementById('form-fields-preview');
        if (!container) return;

        container.innerHTML = '<p class="text-xs text-stone-400">Loading form fields...</p>';

        try {
            const url = WF_DATA.routes.pageFields.replace('__PAGE__', pageId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken },
            });
            const json = await res.json();
            if (json.success && json.fields.length > 0) {
                container.innerHTML = `
                    <div class="mt-2 border border-stone-200 rounded-lg overflow-hidden">
                        <div class="px-3 py-2 bg-stone-100 border-b border-stone-200 flex items-center gap-2 cursor-pointer select-none" onclick="document.getElementById('form-fields-list').classList.toggle('hidden'); this.querySelector('.chevron-icon').classList.toggle('rotate-180');">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#78716c" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            <span class="text-xs font-semibold text-stone-600 flex-1">${json.page.name} — ${json.fields.length} fields</span>
                            <svg class="chevron-icon w-4 h-4 text-stone-400 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div id="form-fields-list" class="p-2 space-y-1 max-h-64 overflow-y-auto">
                            ${json.fields.map(f => this.renderFieldPreviewItem(f)).join('')}
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<p class="text-xs text-stone-400 mt-1">No fields defined in this form yet.</p>';
            }
        } catch (e) {
            container.innerHTML = '<p class="text-xs text-red-500 mt-1">Failed to load form fields.</p>';
        }
    },

    renderFieldPreviewItem(f) {
        let extra = '';

        // Show options for select/radio
        if ((f.field_type === 'select' || f.field_type === 'radio') && f.options && f.options.length > 0) {
            const opts = Array.isArray(f.options) ? f.options.slice(0, 5) : [];
            extra = `<div class="ml-7 mt-0.5 flex flex-wrap gap-1">${opts.map(o => `<span class="text-[9px] px-1 py-0.5 bg-blue-50 text-blue-600 rounded">${typeof o === 'object' ? (o.label || o.value || o) : o}</span>`).join('')}${f.options.length > 5 ? `<span class="text-[9px] text-stone-400">+${f.options.length - 5} more</span>` : ''}</div>`;
        }

        // Show repeater columns
        if (f.field_type === 'repeater' && f.repeater_columns && f.repeater_columns.length > 0) {
            extra = `
                <div class="ml-7 mt-1 border-l-2 border-stone-200 pl-2 space-y-0.5">
                    ${f.repeater_columns.map(col => `
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 flex items-center justify-center rounded bg-amber-50 text-amber-600">${this.getFieldTypeIcon(col.type)}</span>
                            <span class="text-[10px] font-medium text-stone-600">${col.label}</span>
                            <span class="text-[9px] px-1 py-0.5 rounded bg-stone-50 text-stone-400 font-mono">${col.type}</span>
                            ${col.required ? '<span class="text-[9px] text-red-500 font-bold">*</span>' : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Show default value
        if (f.default_value) {
            extra += `<div class="ml-7 mt-0.5"><span class="text-[9px] text-stone-400">Default: ${f.default_value}</span></div>`;
        }

        // Col span indicator
        const colBadge = f.col_span && f.col_span > 1 ? `<span class="text-[9px] px-1 py-0.5 rounded bg-purple-50 text-purple-500">col-${f.col_span}</span>` : '';

        return `
            <div class="px-2 py-1.5 rounded bg-white border border-stone-100">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 flex items-center justify-center rounded bg-stone-100 text-stone-500">${this.getFieldTypeIcon(f.field_type)}</span>
                    <span class="text-xs font-medium text-stone-700 flex-1">${f.label || f.field_name}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-stone-100 text-stone-500 font-mono">${f.field_type}</span>
                    ${colBadge}
                    ${f.is_required ? '<span class="text-[10px] text-red-500 font-bold">*</span>' : ''}
                </div>
                ${extra}
            </div>
        `;
    },

    getFieldTypeIcon(type) {
        const icons = {
            title: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>',
            content: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>',
            text: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
            textarea: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>',
            number: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
            decimal: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
            currency: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            select: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>',
            radio: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4" fill="currentColor"/></svg>',
            date: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            datetime: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="16" cy="16" r="2"/></svg>',
            time: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            file: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
            image: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
            checkbox: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
            toggle: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="5" width="22" height="14" rx="7"/><circle cx="16" cy="12" r="3"/></svg>',
            email: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            phone: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
            url: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
            password: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            slug: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
            color: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
            rating: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            json: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            repeater: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="6" rx="1"/><rect x="3" y="10" width="18" height="6" rx="1"/><line x1="3" y1="19" x2="21" y2="19"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="9" y1="22" x2="15" y2="22"/></svg>',
        };
        return icons[type] || '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
    },

    // ── ACTIONS TAB ──────────────────────────────────────────────────────────

    renderActionsTab(stage) {
        const assignedKeys = stage.actions.map(a => a.action_key);
        const groups = {};
        (WF_DATA.allActions || []).forEach(a => {
            if (!groups[a.group]) groups[a.group] = [];
            groups[a.group].push(a);
        });

        // Build assigned actions section (drag-sortable)
        const assignedHtml = assignedKeys.length > 0 ? `
            <div class="mb-3">
                <div class="text-[10px] font-bold text-stone-400 uppercase tracking-wider mb-1">Assigned Actions (drag to reorder, click ⚙ to configure)</div>
                <div id="assigned-actions-list" class="space-y-1">
                    ${stage.actions.map(a => {
                        const def = (WF_DATA.allActions || []).find(d => d.action_key === a.action_key);
                        if (!def) return '';
                        const hasNotify = a.notify_enabled;
                        return `<div class="assigned-action-item flex items-center gap-2 px-2 py-1.5 rounded border border-red-200 bg-red-50" data-key="${a.action_key}" data-map-id="${a.id}">
                            <span class="drag-handle text-stone-300 cursor-grab">⠿</span>
                            <span class="text-xs font-medium text-stone-700 flex-1">${def.display_label}</span>
                            ${hasNotify ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>' : ''}
                            <button class="btn-action-config w-5 h-5 flex items-center justify-center rounded hover:bg-red-100 text-stone-400 hover:text-stone-600" data-map-id="${a.id}" title="Configure notification">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            </button>
                            <span class="wf-action-badge ${def.button_style}">${def.button_style}</span>
                        </div>`;
                    }).join('')}
                </div>
            </div>
            <div id="action-config-panel"></div>
            <hr class="my-3 border-stone-200">
        ` : '';

        return `
            <div class="mb-3">
                <input type="text" id="action-search" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-xs" placeholder="Search actions...">
            </div>
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs text-stone-500"><span id="action-count">${assignedKeys.length}</span> actions assigned</span>
                <span id="action-save-status" class="text-xs"></span>
            </div>
            ${assignedHtml}
            <div id="action-groups" class="space-y-3 max-h-[calc(100vh-420px)] overflow-y-auto">
                ${Object.entries(groups).map(([group, actions]) => `
                    <div class="action-group" data-group="${group}">
                        <div class="text-[10px] font-bold text-stone-400 uppercase tracking-wider mb-1">${group}</div>
                        <div class="space-y-1">
                            ${actions.map(a => `
                                <label class="action-item flex items-center gap-2 px-2 py-1.5 rounded border cursor-pointer transition-colors ${assignedKeys.includes(a.action_key) ? 'bg-red-50 border-red-200' : 'bg-white border-stone-100 hover:bg-stone-50'}" data-key="${a.action_key}" data-label="${a.display_label.toLowerCase()}">
                                    <input type="checkbox" class="action-checkbox rounded text-red-700" value="${a.action_key}" ${assignedKeys.includes(a.action_key) ? 'checked' : ''}>
                                    <span class="text-xs font-medium text-stone-700 flex-1">${a.display_label}</span>
                                    <span class="wf-action-badge ${a.button_style}">${a.button_style}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    bindActionEvents(stage) {
        // Search filter
        document.getElementById('action-search')?.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('.action-item').forEach(item => {
                const label = item.dataset.label;
                const key = item.dataset.key;
                const match = label.includes(q) || key.includes(q);
                item.style.display = match ? '' : 'none';
            });
            document.querySelectorAll('.action-group').forEach(group => {
                const items = group.querySelectorAll('.action-item');
                const hasVisible = Array.from(items).some(i => i.style.display !== 'none');
                group.style.display = hasVisible ? '' : 'none';
            });
        });

        // Auto-save on checkbox change
        document.querySelectorAll('.action-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const label = cb.closest('.action-item');
                label.classList.toggle('bg-red-50', cb.checked);
                label.classList.toggle('border-red-200', cb.checked);
                label.classList.toggle('bg-white', !cb.checked);
                label.classList.toggle('border-stone-100', !cb.checked);

                const count = document.querySelectorAll('.action-checkbox:checked').length;
                document.getElementById('action-count').textContent = count;

                this.autoSaveActions(stage.id);
            });
        });

        // Init Sortable on assigned actions list
        const sortableEl = document.getElementById('assigned-actions-list');
        if (sortableEl && typeof Sortable !== 'undefined') {
            Sortable.create(sortableEl, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: () => {
                    this.autoSaveActions(stage.id);
                }
            });
        }

        // Action config button click
        document.querySelectorAll('.btn-action-config').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const mapId = parseInt(btn.dataset.mapId);
                const action = stage.actions.find(a => a.id === mapId);
                if (action) this.showActionConfigPanel(action, stage);
            });
        });
    },

    showActionConfigPanel(action, stage) {
        const panel = document.getElementById('action-config-panel');
        if (!panel) return;

        const isApprovalType = ['approve', 'reject', 'send_for_approval', 'final_approve', 'hold', 'send_back', 'escalate', 'edit_entry'].includes(action.action_key) ||
            (action.logic_type && ['stage_move', 'notification'].includes(action.logic_type));

        const recipientRoleIds = (action.notify_recipients && action.notify_recipients.ids) || [];
        const recipientUserIds = action.notify_user_ids || [];

        panel.innerHTML = `
            <div class="border border-stone-200 rounded-lg p-3 bg-stone-50 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-stone-700">${action.display_label} — Settings</span>
                    <button id="close-action-config" class="text-stone-400 hover:text-stone-600">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="ac-notify-enabled" class="rounded text-red-700" ${action.notify_enabled ? 'checked' : ''}>
                        <span class="text-xs font-medium text-stone-700">Enable Notification</span>
                    </label>
                    <div id="ac-notify-fields" class="${action.notify_enabled ? '' : 'hidden'} space-y-3">

                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Medium</label>
                            <select id="ac-notify-medium" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                                <option value="email" ${action.notify_medium === 'email' ? 'selected' : ''}>Email</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Assign to Roles</label>
                            <div class="mt-1 space-y-1 max-h-20 overflow-y-auto border border-stone-200 rounded p-2 bg-white">
                                ${(WF_DATA.allRoles || []).map(r => `<label class="flex items-center gap-2"><input type="checkbox" class="ac-recipient-role rounded text-red-700" value="${r.id}" ${recipientRoleIds.includes(r.id) ? 'checked' : ''}><span class="text-xs">${r.name}</span></label>`).join('')}
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Assign to Users (direct)</label>
                            <div class="mt-1 space-y-1 max-h-20 overflow-y-auto border border-stone-200 rounded p-2 bg-white">
                                ${(WF_DATA.allUsers || []).map(u => `<label class="flex items-center gap-2"><input type="checkbox" class="ac-recipient-user rounded text-red-700" value="${u.id}" ${recipientUserIds.includes(u.id) ? 'checked' : ''}><span class="text-xs">${u.name} <span class="text-stone-400">(${u.email})</span></span></label>`).join('')}
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Frequency</label>
                            <select id="ac-notify-frequency" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                                <option value="once" ${action.notify_frequency === 'once' ? 'selected' : ''}>Once (on action trigger)</option>
                                <option value="daily" ${action.notify_frequency === 'daily' ? 'selected' : ''}>Daily Reminder</option>
                                <option value="on_escalation" ${action.notify_frequency === 'on_escalation' ? 'selected' : ''}>On Escalation Only</option>
                            </select>
                        </div>

                        ${isApprovalType ? `
                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Escalation Time (hours)</label>
                            <input type="number" id="ac-escalation-hours" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs" value="${action.escalation_hours || ''}" placeholder="e.g. 24" min="1" max="720">
                            <p class="text-[9px] text-stone-400 mt-0.5">Escalation triggers if no action within this time</p>
                        </div>` : ''}

                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="ac-notify-next-stage" class="rounded text-red-700" ${action.notify_next_stage ? 'checked' : ''}>
                            <span class="text-xs font-medium text-stone-700">Notify next stage assignees</span>
                        </label>

                        <hr class="border-stone-200">
                        <div class="text-[10px] font-bold text-stone-500 uppercase">Email Template</div>

                        <div>
                            <label class="text-[10px] font-semibold text-stone-500 uppercase">Select Template</label>
                            <select id="ac-email-template-id" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                                <option value="">— No template —</option>
                                ${(WF_DATA.allEmailTemplates || []).map(t => `<option value="${t.id}" ${action.email_template_id == t.id ? 'selected' : ''}>[${t.category}] ${t.name}</option>`).join('')}
                            </select>
                        </div>

                    </div>
                    <button id="btn-save-action-config" class="w-full mt-2 px-3 py-1.5 bg-red-700 text-white text-xs font-semibold rounded hover:bg-red-800 transition-colors">Save Settings</button>
                </div>
            </div>
        `;

        // Toggle notify fields visibility
        document.getElementById('ac-notify-enabled').addEventListener('change', (e) => {
            document.getElementById('ac-notify-fields').classList.toggle('hidden', !e.target.checked);
        });

        // Close button
        document.getElementById('close-action-config').addEventListener('click', () => {
            panel.innerHTML = '';
        });

        // Save button
        document.getElementById('btn-save-action-config').addEventListener('click', async () => {
            const roleIds = [];
            document.querySelectorAll('.ac-recipient-role:checked').forEach(cb => roleIds.push(parseInt(cb.value)));
            const userIds = [];
            document.querySelectorAll('.ac-recipient-user:checked').forEach(cb => userIds.push(parseInt(cb.value)));

            const templateId = document.getElementById('ac-email-template-id')?.value;

            const data = {
                notify_enabled: document.getElementById('ac-notify-enabled').checked,
                notify_medium: document.getElementById('ac-notify-medium').value,
                notify_recipients: { type: 'roles', ids: roleIds },
                notify_user_ids: userIds,
                notify_frequency: document.getElementById('ac-notify-frequency').value,
                escalation_hours: document.getElementById('ac-escalation-hours')?.value ? parseInt(document.getElementById('ac-escalation-hours').value) : null,
                notify_next_stage: document.getElementById('ac-notify-next-stage').checked,
                email_template_id: templateId ? parseInt(templateId) : null,
            };

            const url = WF_DATA.routes.actionMapUpdate.replace('__MAP__', action.id);
            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(data),
                });
                const json = await res.json();
                if (json.success) {
                    Object.assign(action, data);
                    panel.innerHTML = '';
                    _showGlobalToast('success', 'Settings saved');
                    const updatedStage = WF_DATA.stages.find(s => s.id === stage.id);
                    document.getElementById('drawer-content').innerHTML = this.renderActionsTab(updatedStage);
                    this.bindActionEvents(updatedStage);
                }
            } catch (e) {
                _showGlobalToast('error', 'Failed to save settings');
            }
        });
    },

    autoSaveActions(stageId) {
        clearTimeout(this._actionSaveTimer);
        this._actionSaveTimer = setTimeout(async () => {
            // Get ordered keys from sortable list first, then add any newly checked ones
            const orderedKeys = [];
            document.querySelectorAll('#assigned-actions-list .assigned-action-item').forEach(el => {
                orderedKeys.push(el.dataset.key);
            });

            // Add newly checked keys not in the sorted list
            document.querySelectorAll('.action-checkbox:checked').forEach(cb => {
                if (!orderedKeys.includes(cb.value)) {
                    orderedKeys.push(cb.value);
                }
            });

            // Remove unchecked keys from the list
            const checkedKeys = [];
            document.querySelectorAll('.action-checkbox:checked').forEach(cb => {
                checkedKeys.push(cb.value);
            });
            const finalKeys = orderedKeys.filter(k => checkedKeys.includes(k));

            const statusEl = document.getElementById('action-save-status');
            if (statusEl) statusEl.innerHTML = '<span class="text-amber-600">saving...</span>';

            const url = WF_DATA.routes.stageActions.replace('__STAGE__', stageId);
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ action_keys: finalKeys }),
                });
                const json = await res.json();
                if (json.success) {
                    const stage = WF_DATA.stages.find(s => s.id === stageId);
                    if (stage) stage.actions = json.actions;
                    if (statusEl) statusEl.innerHTML = '<span class="text-green-600">✓ saved</span>';
                    setTimeout(() => { if (statusEl) statusEl.innerHTML = ''; }, 2000);
                    this.renderPipeline();
                    this.renderStageLibrary();
                    // Re-render to update the assigned list
                    const updatedStage = WF_DATA.stages.find(s => s.id === stageId);
                    if (updatedStage) {
                        document.getElementById('drawer-content').innerHTML = this.renderActionsTab(updatedStage);
                        this.bindActionEvents(updatedStage);
                    }
                }
            } catch (e) {
                if (statusEl) statusEl.innerHTML = '<span class="text-red-600">✗ error</span>';
            }
        }, 400);
    },

    // ── LAYOUT TAB ─────────────────────────────────────────────────────────

    renderLayoutTab(stage) {
        const currentLayout = stage.layout_template || 'form_sidebar';

        return `
            <div class="mb-4">
                <label class="text-xs font-bold text-stone-500 uppercase mb-2 block">Page Layout Template</label>
                <div class="grid grid-cols-3 gap-2">
                    <div class="layout-option cursor-pointer rounded-lg border-2 p-2 text-center transition-all ${currentLayout === 'form_sidebar' ? 'border-red-700 bg-red-50' : 'border-stone-200 hover:border-stone-400'}" data-layout="form_sidebar">
                        <div class="flex gap-1 h-16 mb-1.5">
                            <div class="flex-[2] bg-stone-200 rounded"></div>
                            <div class="flex-1 bg-stone-300 rounded"></div>
                        </div>
                        <span class="text-[10px] font-semibold ${currentLayout === 'form_sidebar' ? 'text-red-700' : 'text-stone-600'}">Form + Sidebar</span>
                    </div>
                    <div class="layout-option cursor-pointer rounded-lg border-2 p-2 text-center transition-all ${currentLayout === 'split_panel' ? 'border-red-700 bg-red-50' : 'border-stone-200 hover:border-stone-400'}" data-layout="split_panel">
                        <div class="flex gap-1 h-16 mb-1.5">
                            <div class="flex-1 bg-stone-200 rounded"></div>
                            <div class="flex-1 bg-stone-300 rounded"></div>
                        </div>
                        <span class="text-[10px] font-semibold ${currentLayout === 'split_panel' ? 'text-red-700' : 'text-stone-600'}">Split Panel</span>
                    </div>
                    <div class="layout-option cursor-pointer rounded-lg border-2 p-2 text-center transition-all ${currentLayout === 'full_dashboard' ? 'border-red-700 bg-red-50' : 'border-stone-200 hover:border-stone-400'}" data-layout="full_dashboard">
                        <div class="h-16 mb-1.5">
                            <div class="flex gap-1 h-5 mb-1"><div class="flex-1 bg-stone-300 rounded"></div><div class="flex-1 bg-stone-300 rounded"></div><div class="flex-1 bg-stone-300 rounded"></div></div>
                            <div class="bg-stone-200 rounded h-10"></div>
                        </div>
                        <span class="text-[10px] font-semibold ${currentLayout === 'full_dashboard' ? 'text-red-700' : 'text-stone-600'}">Full Dashboard</span>
                    </div>
                </div>
                <p class="text-[10px] text-stone-400 mt-2">
                    ${currentLayout === 'form_sidebar' ? 'Form on left, actions on right sidebar' : currentLayout === 'split_panel' ? 'Upload/form on left, data table on right' : 'Counters on top, form + table below'}
                </p>
            </div>
            <hr class="my-3 border-stone-200">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-bold text-stone-500 uppercase">Layout Widgets</span>
                <button id="btn-add-widget" class="text-xs px-2 py-1 bg-red-700 text-white rounded hover:bg-red-800">+ Add Widget</button>
            </div>
            <div id="layout-widgets-list" class="space-y-2 mb-4">
                <p class="text-xs text-stone-400" id="widgets-loading">Loading widgets...</p>
            </div>
            <div id="widget-form-panel"></div>
        `;
    },

    bindLayoutEvents(stage) {
        // Layout template selector
        document.querySelectorAll('.layout-option').forEach(opt => {
            opt.addEventListener('click', () => {
                const layout = opt.dataset.layout;
                document.querySelectorAll('.layout-option').forEach(o => {
                    o.classList.remove('border-red-700', 'bg-red-50');
                    o.classList.add('border-stone-200');
                    o.querySelector('span').classList.remove('text-red-700');
                    o.querySelector('span').classList.add('text-stone-600');
                });
                opt.classList.remove('border-stone-200');
                opt.classList.add('border-red-700', 'bg-red-50');
                opt.querySelector('span').classList.remove('text-stone-600');
                opt.querySelector('span').classList.add('text-red-700');
                this.saveStageProperty(stage.id, 'layout_template', { value: layout, type: 'text' });
                stage.layout_template = layout;
            });
        });

        this.loadLayoutWidgets(stage.id);

        document.getElementById('btn-add-widget')?.addEventListener('click', () => {
            this.showWidgetForm(stage.id, null);
        });
    },

    async loadLayoutWidgets(stageId) {
        const container = document.getElementById('layout-widgets-list');
        const url = WF_DATA.routes.stageWidgets.replace('__STAGE__', stageId);
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json.success) {
                if (json.widgets.length === 0) {
                    container.innerHTML = '<p class="text-xs text-stone-400 text-center py-4">No widgets configured. Click "+ Add Widget" to start.</p>';
                } else {
                    container.innerHTML = json.widgets.map((w, i) => `
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-stone-200 bg-white" data-widget-id="${w.id}">
                            <span class="drag-handle text-stone-300 cursor-grab">⠿</span>
                            <span class="w-6 h-6 rounded flex items-center justify-center bg-stone-100 text-stone-500 text-[10px] font-bold">${this.getWidgetIcon(w.widget_type)}</span>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-stone-700">${w.title}</div>
                                <div class="text-[10px] text-stone-400">${w.widget_type} • col-${w.col_span}</div>
                            </div>
                            <button class="btn-remove-widget text-stone-400 hover:text-red-600" data-idx="${i}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    `).join('');

                    // Store widgets locally
                    this._currentWidgets = json.widgets;

                    // Bind remove
                    container.querySelectorAll('.btn-remove-widget').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const idx = parseInt(btn.dataset.idx);
                            this._currentWidgets.splice(idx, 1);
                            this.saveLayoutWidgets(stageId);
                        });
                    });

                    // Init sortable
                    if (typeof Sortable !== 'undefined') {
                        Sortable.create(container, {
                            animation: 150,
                            handle: '.drag-handle',
                            onEnd: () => {
                                const reordered = [];
                                container.querySelectorAll('[data-widget-id]').forEach(el => {
                                    const wid = parseInt(el.dataset.widgetId);
                                    const w = this._currentWidgets.find(x => x.id === wid);
                                    if (w) reordered.push(w);
                                });
                                this._currentWidgets = reordered;
                                this.saveLayoutWidgets(stageId);
                            }
                        });
                    }
                }
            }
        } catch (e) {
            container.innerHTML = '<p class="text-xs text-red-500">Failed to load widgets.</p>';
        }
    },

    showWidgetForm(stageId, existingWidget) {
        const panel = document.getElementById('widget-form-panel');
        const w = existingWidget || { widget_type: 'counter', title: '', col_span: 1, config: {} };

        panel.innerHTML = `
            <div class="border border-stone-200 rounded-lg p-3 bg-stone-50">
                <div class="text-xs font-bold text-stone-700 mb-3">Add Widget</div>
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] font-semibold text-stone-500 uppercase">Widget Type</label>
                        <select id="wgt-type" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                            <option value="counter">Counter Card</option>
                            <option value="chart">Chart</option>
                            <option value="table">Data Table</option>
                            <option value="entry_form">Entry Form</option>
                            <option value="file_upload">File Upload</option>
                            <option value="recent_entries">Recent Entries</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-stone-500 uppercase">Title</label>
                        <input type="text" id="wgt-title" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs" placeholder="e.g. Total Scanned" value="${w.title}">
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-stone-500 uppercase">Column Span (1-3)</label>
                        <select id="wgt-col-span" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                            <option value="1" ${w.col_span == 1 ? 'selected' : ''}>1 Column</option>
                            <option value="2" ${w.col_span == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${w.col_span == 3 ? 'selected' : ''}>Full Width (3)</option>
                        </select>
                    </div>
                    <div id="wgt-config-fields"></div>
                    <div class="flex gap-2">
                        <button id="btn-cancel-widget" class="flex-1 px-3 py-1.5 bg-stone-200 text-stone-700 text-xs font-semibold rounded">Cancel</button>
                        <button id="btn-save-widget" class="flex-1 px-3 py-1.5 bg-red-700 text-white text-xs font-semibold rounded">Add</button>
                    </div>
                </div>
            </div>
        `;

        // Show type-specific config
        const typeSelect = document.getElementById('wgt-type');
        this.renderWidgetConfigFields(typeSelect.value);
        typeSelect.addEventListener('change', () => this.renderWidgetConfigFields(typeSelect.value));

        document.getElementById('btn-cancel-widget').addEventListener('click', () => {
            panel.innerHTML = '';
        });

        document.getElementById('btn-save-widget').addEventListener('click', () => {
            const newWidget = {
                widget_type: document.getElementById('wgt-type').value,
                title: document.getElementById('wgt-title').value || 'Untitled',
                col_span: parseInt(document.getElementById('wgt-col-span').value),
                config: this.getWidgetConfigValues(),
            };

            if (!this._currentWidgets) this._currentWidgets = [];
            this._currentWidgets.push(newWidget);
            this.saveLayoutWidgets(stageId);
            panel.innerHTML = '';
        });
    },

    renderWidgetConfigFields(type) {
        const container = document.getElementById('wgt-config-fields');
        if (!container) return;

        let html = '';
        if (type === 'entry_form') {
            html = `
                <label class="text-[10px] font-semibold text-stone-500 uppercase">Open Mode</label>
                <select id="wgt-cfg-open-mode" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                    <option value="inline">Inline (same page)</option>
                    <option value="modal">Modal Popup</option>
                    <option value="new_page">New Page</option>
                </select>
            `;
        } else if (type === 'counter') {
            html = `
                <label class="text-[10px] font-semibold text-stone-500 uppercase">Counter Color</label>
                <input type="color" id="wgt-cfg-color" class="w-full mt-1 h-8 border border-stone-300 rounded" value="#7f1d1d">
                <label class="text-[10px] font-semibold text-stone-500 uppercase mt-2">Icon Class</label>
                <input type="text" id="wgt-cfg-icon" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs" placeholder="fa-solid fa-file" value="fa-solid fa-file">
            `;
        } else if (type === 'chart') {
            html = `
                <label class="text-[10px] font-semibold text-stone-500 uppercase">Chart Type</label>
                <select id="wgt-cfg-chart-type" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs">
                    <option value="bar">Bar Chart</option>
                    <option value="line">Line Chart</option>
                    <option value="pie">Pie Chart</option>
                    <option value="doughnut">Doughnut</option>
                </select>
            `;
        } else if (type === 'table' || type === 'recent_entries') {
            html = `
                <label class="text-[10px] font-semibold text-stone-500 uppercase">Rows to Show</label>
                <input type="number" id="wgt-cfg-limit" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs" value="10" min="5" max="100">
            `;
        } else if (type === 'file_upload') {
            html = `
                <label class="text-[10px] font-semibold text-stone-500 uppercase">Max File Size (MB)</label>
                <input type="number" id="wgt-cfg-max-size" class="w-full mt-1 px-2 py-1.5 border border-stone-300 rounded text-xs" value="10" min="1">
            `;
        }
        container.innerHTML = html;
    },

    getWidgetConfigValues() {
        const config = {};
        const openMode = document.getElementById('wgt-cfg-open-mode');
        const color = document.getElementById('wgt-cfg-color');
        const icon = document.getElementById('wgt-cfg-icon');
        const chartType = document.getElementById('wgt-cfg-chart-type');
        const limit = document.getElementById('wgt-cfg-limit');
        const maxSize = document.getElementById('wgt-cfg-max-size');

        if (openMode) config.open_mode = openMode.value;
        if (color) config.color = color.value;
        if (icon) config.icon = icon.value;
        if (chartType) config.chart_type = chartType.value;
        if (limit) config.limit = parseInt(limit.value);
        if (maxSize) config.max_file_size_mb = parseInt(maxSize.value);

        return config;
    },

    async saveLayoutWidgets(stageId) {
        const url = WF_DATA.routes.stageWidgetsSave.replace('__STAGE__', stageId);
        const widgets = (this._currentWidgets || []).map(w => ({
            widget_type: w.widget_type,
            title: w.title,
            col_span: w.col_span,
            config: w.config,
        }));

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ widgets }),
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', 'Layout saved');
                this.loadLayoutWidgets(stageId);
            }
        } catch (e) {
            _showGlobalToast('error', 'Failed to save layout');
        }
    },

    getWidgetIcon(type) {
        const icons = { counter: '#', chart: '📊', table: '☰', entry_form: '📝', file_upload: '📎', recent_entries: '📋' };
        return icons[type] || '□';
    },

    bindConfigEvents(stage) {
        // Auto-save on change for config fields
        document.querySelectorAll('[data-config]').forEach(input => {
            input.addEventListener('change', () => {
                this.saveStageConfig(stage.id);
            });
        });

        // Auto-save for stage properties
        document.querySelectorAll('[data-stage-prop]').forEach(input => {
            input.addEventListener('change', () => {
                this.saveStageProperty(stage.id, input.dataset.stageProp, input);
            });
        });

        // Test API button
        document.getElementById('btn-test-api')?.addEventListener('click', () => {
            alert('API test would go here.');
        });
    },

    async saveStageProperty(stageId, property, input) {
        let value = input.type === 'checkbox' ? input.checked : input.value;
        // page_id: empty string means null (unlink form)
        if (property === 'page_id') {
            value = value ? parseInt(value) : null;
        }
        const stage = WF_DATA.stages.find(s => s.id === stageId);
        if (!stage) return;

        const url = WF_DATA.routes.stageUpdate.replace('__STAGE__', stageId);
        try {
            this.showSaveStatus('saving');
            const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ [property]: value }),
            });
            const json = await res.json();
            if (json.success) {
                stage[property] = value;
                this.showSaveStatus('saved');
                this.renderStageLibrary();
                this.renderPipeline();
                // Re-render config tab to show/hide "Edit Form" link
                if (property === 'page_id' && this.activeStageId === stageId) {
                    this.renderDrawerTab('config', stage);
                }
            }
        } catch (e) {
            this.showSaveStatus('error');
        }
    },

    async saveStageConfig(stageId) {
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(async () => {
            this.showSaveStatus('saving');

            const configData = {};
            document.querySelectorAll('[data-config]').forEach(input => {
                const key = input.dataset.config;
                if (input.type === 'checkbox') {
                    configData[key] = input.checked;
                } else if (input.type === 'number') {
                    configData[key] = input.value ? parseFloat(input.value) : null;
                } else if (key === 'allowed_extensions') {
                    configData[key] = input.value.split(',').map(ext => ext.trim()).filter(ext => ext);
                } else {
                    configData[key] = input.value;
                }
            });

            const url = WF_DATA.routes.stageUpdate.replace('__STAGE__', stageId);
            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ config: configData }),
                });
                const json = await res.json();
                if (json.success) {
                    const stage = WF_DATA.stages.find(s => s.id === stageId);
                    if (stage) stage.config = json.stage.config;
                    this.showSaveStatus('saved');
                }
            } catch (e) {
                this.showSaveStatus('error');
            }
        }, 500);
    },
    // ── TOGGLE STAGE ACTIVE ──────────────────────────────────────────────────

    async toggleStageActive(stageId) {
        const stage = WF_DATA.stages.find(s => s.id === stageId);
        if (!stage) return;

        const newState = !stage.is_active;
        const url = WF_DATA.routes.stageUpdate.replace('__STAGE__', stageId);

        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ is_active: newState }),
            });
            const json = await res.json();
            if (json.success) {
                stage.is_active = newState;
                this.showSaveStatus('saved');
                this.renderStageLibrary();
                this.renderPipeline();
            }
        } catch (e) {
            this.showSaveStatus('error');
        }
    },

    // ── SAVE STATUS ──────────────────────────────────────────────────────────

    showSaveStatus(state) {
        const el = document.getElementById('wf-save-status');
        if (state === 'saving') {
            el.innerHTML = '<span class="text-amber-600">● Saving...</span>';
        } else if (state === 'saved') {
            el.innerHTML = '<span class="text-green-600">✓ Saved</span>';
            setTimeout(() => { el.innerHTML = ''; }, 2000);
        } else if (state === 'error') {
            el.innerHTML = '<span class="text-red-600">✗ Error</span>';
        }
    },

    // ── ADD NEW STAGE ────────────────────────────────────────────────────────

    showAddStageModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-stone-800 mb-4">Add New Stage</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">Stage Name</label>
                        <input type="text" id="new-stage-name" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-sm" placeholder="e.g., Quality Check">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">System Key</label>
                        <input type="text" id="new-stage-key" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-sm" placeholder="e.g., quality_check">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">Icon Class</label>
                        <input type="text" id="new-stage-icon" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-sm" value="fa-solid fa-circle">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">Color</label>
                        <input type="color" id="new-stage-color" class="w-full h-10 border border-stone-300 rounded-lg" value="#64748b">
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button id="btn-cancel-stage" class="flex-1 px-4 py-2 bg-stone-200 text-stone-700 rounded-lg text-sm font-semibold">Cancel</button>
                    <button id="btn-create-stage" class="flex-1 px-4 py-2 bg-red-700 text-white rounded-lg text-sm font-semibold">Create Stage</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Auto-generate system key from name
        document.getElementById('new-stage-name').addEventListener('input', (e) => {
            const name = e.target.value;
            const key = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            document.getElementById('new-stage-key').value = key;
        });

        document.getElementById('btn-cancel-stage').addEventListener('click', () => {
            modal.remove();
        });

        document.getElementById('btn-create-stage').addEventListener('click', async () => {
            const name = document.getElementById('new-stage-name').value.trim();
            const key = document.getElementById('new-stage-key').value.trim();
            const icon = document.getElementById('new-stage-icon').value.trim();
            const color = document.getElementById('new-stage-color').value;

            if (!name || !key) {
                alert('Please fill in stage name and system key.');
                return;
            }

            await this.createStage({ system_key: key, display_name: name, icon, color });
            modal.remove();
        });
    },

    async createStage(data) {
        const url = WF_DATA.routes.stageStore;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                const newStage = {
                    id: json.stage.id,
                    system_key: json.stage.system_key,
                    display_name: json.stage.display_name,
                    position: json.stage.position,
                    is_active: json.stage.is_active,
                    is_optional: json.stage.is_optional,
                    icon: json.stage.icon,
                    color: json.stage.color,
                    config: (json.stage.config && !Array.isArray(json.stage.config)) ? json.stage.config : {},
                    page_id: null,
                    layout_template: 'form_sidebar',
                    actions: [],
                    sub_stages: [],
                };

                if (data.parent_stage_id) {
                    // Add as sub-stage
                    const parent = WF_DATA.stages.find(s => s.id === data.parent_stage_id);
                    if (parent) {
                        if (!parent.sub_stages) parent.sub_stages = [];
                        parent.sub_stages.push(newStage);
                    }
                } else {
                    WF_DATA.stages.push(newStage);
                }

                this.showSaveStatus('saved');
                this.renderStageLibrary();
                this.renderPipeline();
                _showGlobalToast('success', data.parent_stage_id ? 'Sub-stage created' : 'Stage created');
            }
        } catch (e) {
            this.showSaveStatus('error');
            _showGlobalToast('error', 'Failed to create stage');
        }
    },

    showAddSubStageModal(parentStageId) {
        const parent = WF_DATA.stages.find(s => s.id === parentStageId);
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-stone-800 mb-1">Add Sub-Stage</h3>
                <p class="text-xs text-stone-400 mb-4">Under: ${parent ? parent.display_name : 'Stage'}</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">Sub-Stage Name</label>
                        <input type="text" id="new-substage-name" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-sm" placeholder="e.g., Upload Supporting File">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1">System Key</label>
                        <input type="text" id="new-substage-key" class="w-full px-3 py-2 border border-stone-300 rounded-lg text-sm" placeholder="e.g., upload_supporting">
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button id="btn-cancel-substage" class="flex-1 px-4 py-2 bg-stone-200 text-stone-700 rounded-lg text-sm font-semibold">Cancel</button>
                    <button id="btn-create-substage" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Create Sub-Stage</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        document.getElementById('new-substage-name').addEventListener('input', (e) => {
            document.getElementById('new-substage-key').value = e.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        });

        document.getElementById('btn-cancel-substage').addEventListener('click', () => modal.remove());

        document.getElementById('btn-create-substage').addEventListener('click', async () => {
            const name = document.getElementById('new-substage-name').value.trim();
            const key = document.getElementById('new-substage-key').value.trim();
            if (!name || !key) { alert('Please fill in name and key.'); return; }
            await this.createStage({ system_key: key, display_name: name, icon: 'fa-solid fa-circle-dot', color: parent ? parent.color : '#6366f1', parent_stage_id: parentStageId });
            modal.remove();
        });
    },

    async deleteStage(stageId) {
        const stage = WF_DATA.stages.find(s => s.id === stageId);
        if (!stage) return;

        if (!confirm(`Delete "${stage.display_name}"? This cannot be undone.`)) {
            return;
        }

        const url = WF_DATA.routes.stageDestroy.replace('__STAGE__', stageId);
        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                const index = WF_DATA.stages.findIndex(s => s.id === stageId);
                if (index > -1) {
                    WF_DATA.stages.splice(index, 1);
                }
                WF_DATA.stages.forEach((s, i) => {
                    s.position = i + 1;
                });
                this.showSaveStatus('saved');
                this.renderStageLibrary();
                this.renderPipeline();
                if (this.activeStageId === stageId) {
                    this.closeDrawer();
                }
                _showGlobalToast('success', 'Stage deleted successfully');
            } else {
                _showGlobalToast('error', json.message || 'Failed to delete stage');
            }
        } catch (e) {
            this.showSaveStatus('error');
            _showGlobalToast('error', 'Failed to delete stage');
        }
    },

    async deleteSubStage(subStageId) {
        if (!confirm('Delete this sub-stage?')) return;
        const url = WF_DATA.routes.stageDestroy.replace('__STAGE__', subStageId);
        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                // Remove from local data
                WF_DATA.stages.forEach(s => {
                    if (s.sub_stages) {
                        s.sub_stages = s.sub_stages.filter(sub => sub.id !== subStageId);
                    }
                });
                this.renderPipeline();
                this.renderStageLibrary();
                if (this.activeStageId === subStageId) this.closeDrawer();
                _showGlobalToast('success', 'Sub-stage deleted');
            }
        } catch (e) {
            _showGlobalToast('error', 'Failed to delete sub-stage');
        }
    },

    async updateSubStageName(subStageId, newName, parentId) {
        if (!newName) return;
        const parent = WF_DATA.stages.find(s => s.id === parentId);
        if (!parent || !parent.sub_stages) return;
        const sub = parent.sub_stages.find(s => s.id === subStageId);
        if (!sub || sub.display_name === newName) return;

        const url = WF_DATA.routes.stageUpdate.replace('__STAGE__', subStageId);
        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ display_name: newName }),
            });
            const json = await res.json();
            if (json.success) {
                sub.display_name = newName;
                this.showSaveStatus('saved');
            }
        } catch (e) {
            this.showSaveStatus('error');
        }
    },

    async updateStageName(stageId, newName) {
        const stage = WF_DATA.stages.find(s => s.id === stageId);
        if (!stage || stage.display_name === newName) return;

        const url = WF_DATA.routes.stageUpdate.replace('__STAGE__', stageId);
        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ display_name: newName }),
            });
            const json = await res.json();
            if (json.success) {
                stage.display_name = newName;
                this.showSaveStatus('saved');
                this.renderStageLibrary();
            }
        } catch (e) {
            this.showSaveStatus('error');
        }
    },

    // ── BIND EVENTS ──────────────────────────────────────────────────────────

    bindEvents() {
        // Drawer close
        document.getElementById('drawer-close').addEventListener('click', () => this.closeDrawer());

        // Tab switching
        document.querySelectorAll('#drawer-tabs a').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = a.dataset.tab;
                if (this.activeStageId) {
                    let stage;
                    if (this._activeSubStageParentId) {
                        const parent = WF_DATA.stages.find(s => s.id === this._activeSubStageParentId);
                        if (parent && parent.sub_stages) {
                            stage = parent.sub_stages.find(s => s.id === this.activeStageId);
                        }
                    } else {
                        stage = WF_DATA.stages.find(s => s.id === this.activeStageId);
                    }
                    if (stage) this.renderDrawerTab(tab, stage);
                }
            });
        });

        // Add new stage button
        document.getElementById('btn-add-stage')?.addEventListener('click', () => {
            this.showAddStageModal();
        });

        // Publish workflow button
        document.getElementById('btn-publish-workflow')?.addEventListener('click', () => {
            this.publishWorkflow();
        });

        // Close drawer on canvas click
        document.addEventListener('click', (e) => {
            const drawer = document.getElementById('wf-drawer');
            if (drawer.classList.contains('open') &&
                !drawer.contains(e.target) &&
                !e.target.closest('.wf-stage-card')) {
                this.closeDrawer();
            }
        });
    },

    async publishWorkflow() {
        if (!confirm('Publish this workflow? It will become the active default and increment the version.')) {
            return;
        }

        const url = WF_DATA.routes.publish;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': WF_DATA.csrfToken, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', json.message);
                // Reload page to reflect new version
                setTimeout(() => location.reload(), 1500);
            } else {
                // Show validation errors
                const errMsg = json.errors ? json.errors.join('\n') : json.message;
                alert('Publish failed:\n\n' + errMsg);
            }
        } catch (e) {
            _showGlobalToast('error', 'Failed to publish workflow');
        }
    },
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof WF_DATA !== 'undefined') {
        WFDesigner.init();
    }
});
