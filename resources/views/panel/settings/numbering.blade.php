@extends('layouts.app')

@section('title', 'Numbering')
@section('page-title', 'Numbering')

@section('content')
<div x-data="numberingPage()" x-init="init()">

    @include('panel.settings._nav')

    @if($companies->count() > 1)
    <div class="bg-white border border-stone-200 rounded-1xl px-4 py-3 mb-4 flex items-center gap-3">
        <span class="text-xs font-semibold text-stone-500 shrink-0">Company:</span>
        <div class="flex flex-wrap gap-2">
            @foreach($companies as $co)
            <a href="{{ route('settings.numbering', ['company_id' => $co->id]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $co->id == $selectedId ? 'bg-red-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                @if($co->is_default)<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endif
                {{ $co->name }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="flex" style="min-height: 520px;">

            {{-- ── Left col: document type list ── --}}
            <div class="w-64 shrink-0 border-r border-stone-100 flex flex-col" style="max-height: 80vh; overflow-y: auto;">
                <div class="px-3 py-2.5 border-b border-stone-100 bg-stone-50 shrink-0">
                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-2">Document</p>
                    <div class="flex items-center gap-2 bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" placeholder="Search…" class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400 min-w-0">
                        <button x-show="search" @click="search = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto py-1">
                    @foreach($documentTypes as $dt)
                    <button @click="activeDoc = '{{ $dt->key }}'; search = ''"
                            x-show="!search || '{{ strtolower($dt->label) }}'.includes(search.toLowerCase())"
                            :class="activeDoc === '{{ $dt->key }}' ? 'bg-red-50 text-red-700 font-semibold border-r-2 border-red-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800'"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs transition-colors text-left">
                        @if($dt->icon_path)<svg class="w-3.5 h-3.5 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $dt->icon_path }}"/></svg>@else<span class="w-3.5 h-3.5 shrink-0"></span>@endif
                        <span class="truncate">{{ $dt->label }}</span>
                        @php $s = $settings->get($dt->key); @endphp
                        @if($s)<span class="ml-auto font-mono text-[10px] text-stone-400 shrink-0">{{ $s->prefix ?: $dt->default_prefix }}</span>@endif
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- ── Right col: settings panel ── --}}
            <div class="flex-1 min-w-0 overflow-y-auto" style="max-height:80vh;">
                @foreach($documentTypes as $dt)
                @php $setting = $settings->get($dt->key); $approval = $approvalSettings->get($dt->key); @endphp
                <div x-show="activeDoc === '{{ $dt->key }}'" style="display:none">
                    <div class="p-6" x-data="docSettings({{ $setting ? $setting->id : 'null' }}, @js($setting ? $setting->toArray() : null), '{{ $dt->default_prefix }}', '{{ $dt->key }}', {{ $selectedId }}, @js($approval ? $approval->toArray() : null), @js($users->toArray()), {{ $dt->digital_approval ? 'true' : 'false' }})" x-init="initDoc()">

                        {{-- Header --}}
                        <div class="flex items-center gap-3 mb-4">
                            @if($dt->icon_path)
                            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $dt->icon_path }}"/></svg>
                            </div>
                            @endif
                            <div>
                                <h3 class="text-sm font-semibold text-stone-800">{{ $dt->label }}</h3>
                                <p class="text-xs text-stone-400">Configure numbering &amp; approval for {{ strtolower($dt->label) }}</p>
                            </div>
                        </div>

                        {{-- Sub-tabs --}}
                        <div class="flex items-center gap-1 mb-5 border-b border-stone-100">
                            <button @click="subTab = 'numbering'" :class="subTab === 'numbering' ? 'border-red-700 text-red-700' : 'border-transparent text-stone-500 hover:text-stone-700'" class="px-3 py-2 text-xs font-semibold border-b-2 transition-colors">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>Numbering
                            </button>
                            <button @click="subTab = 'approval'" :class="subTab === 'approval' ? 'border-red-700 text-red-700' : 'border-transparent text-stone-500 hover:text-stone-700'" class="px-3 py-2 text-xs font-semibold border-b-2 transition-colors">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Approval
                            </button>
                        </div>

                        {{-- ═══ NUMBERING TAB ═══ --}}
                        <div x-show="subTab === 'numbering'">
                            <div class="mb-6 p-4 bg-gradient-to-r from-stone-50 to-stone-100 border border-stone-200 rounded-xl flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Preview</p>
                                    <p class="text-3xl font-bold text-stone-800 font-mono tracking-wider" x-text="preview"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[10px] text-stone-400 uppercase tracking-wide">Next number</p>
                                    <p class="text-2xl font-bold text-red-700 font-mono" x-text="form.next_number"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold text-stone-400 uppercase tracking-widest">Format</h4>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div><label class="form-label">Prefix</label><input type="text" x-model="form.prefix" @input="updatePreview()" maxlength="20" placeholder="{{ $dt->default_prefix }}" class="form-input font-mono"></div>
                                        <div><label class="form-label">Separator</label><select x-model="form.separator" @change="updatePreview()" class="form-input"><option value="/">/</option><option value="-">-</option><option value=".">.</option><option value="_">_</option><option value="">None</option></select></div>
                                        <div><label class="form-label">Suffix</label><input type="text" x-model="form.suffix" @input="updatePreview()" maxlength="20" class="form-input font-mono"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div><label class="form-label">Next Number</label><input type="number" x-model="form.next_number" @input="updatePreview()" min="1" class="form-input font-mono"></div>
                                        <div><label class="form-label">Zero Padding</label><select x-model="form.pad_length" @change="updatePreview()" class="form-input"><option value="1">1 → 1</option><option value="2">2 → 01</option><option value="3">3 → 001</option><option value="4">4 → 0001</option><option value="5">5 → 00001</option><option value="6">6 → 000001</option></select></div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold text-stone-400 uppercase tracking-widest">Date & Reset</h4>
                                    <div><label class="form-label">Reset Frequency</label><select x-model="form.reset_frequency" class="form-input"><option value="never">Never reset</option><option value="yearly">Reset every year</option><option value="monthly">Reset every month</option></select></div>
                                    <div class="p-4 bg-stone-50 border border-stone-200 rounded-xl space-y-3">
                                        <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" x-model="form.include_date" @change="updatePreview()" class="w-4 h-4 rounded border-stone-300 text-red-700 focus:ring-red-700"><span class="text-sm font-medium text-stone-700">Include date in number</span></label>
                                        <div x-show="form.include_date" class="pl-7"><label class="form-label">Date Format</label><select x-model="form.date_format" @change="updatePreview()" class="form-input"><option value="YYYY-MM">YYYY-MM</option><option value="YYYY">YYYY</option><option value="YYYY-MM-DD">YYYY-MM-DD</option><option value="MM-YYYY">MM-YYYY</option><option value="YYYYMM">YYYYMM</option></select></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-stone-100">
                                <div x-show="saved" x-transition class="flex items-center gap-1.5 text-xs text-green-700 font-semibold" style="display:none"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Saved</div>
                                <button @click="saveNumbering()" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Numbering
                                </button>
                            </div>
                        </div>

                        {{-- ═══ APPROVAL TAB ═══ --}}
                        <div x-show="subTab === 'approval'">

                            {{-- Digital Approval Toggle --}}
                            <div class="mb-5 p-4 border rounded-xl flex items-center justify-between gap-4"
                                :style="approvalForm.is_enabled ? 'border-color:#bbf7d0;background:#f0fdf4' : 'border-color:#e7e5e4'">
                                <div>
                                    <p class="text-sm font-semibold text-stone-800">Digital Approval</p>
                                    <p class="text-xs mt-0.5" :style="approvalForm.is_enabled ? 'color:#16a34a' : 'color:#a8a29e'" x-text="approvalForm.is_enabled ? 'Enabled — approval workflow is active' : 'Disabled — click to enable approval workflow'"></p>
                                </div>
                                <button type="button" @click="approvalForm.is_enabled = !approvalForm.is_enabled"
                                    class="shrink-0" style="width:44px;height:24px;border-radius:12px;position:relative;transition:background .2s;cursor:pointer;outline:none;"
                                    :style="approvalForm.is_enabled ? 'background:#16a34a' : 'background:#d6d3d1'">
                                    <div style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.15);transition:transform .2s;"
                                        :style="approvalForm.is_enabled ? 'transform:translateX(20px)' : ''"></div>
                                </button>
                            </div>

                            <div x-show="approvalForm.is_enabled" x-transition>
                                <div class="mb-5">
                                    <label class="form-label mb-2">Approval Mode</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button @click="approvalForm.approval_mode = 'required'" :class="approvalForm.approval_mode === 'required' ? 'border-red-700 bg-red-50 text-red-700' : 'border-stone-200 text-stone-600 hover:border-stone-300'" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span class="text-[11px] font-semibold">Required</span>
                                        </button>
                                        <button @click="approvalForm.approval_mode = 'auto_approved'" :class="approvalForm.approval_mode === 'auto_approved' ? 'border-green-600 bg-green-50 text-green-700' : 'border-stone-200 text-stone-600 hover:border-stone-300'" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span class="text-[11px] font-semibold">Auto Approved</span>
                                        </button>
                                        <button @click="approvalForm.approval_mode = 'no_approval'" :class="approvalForm.approval_mode === 'no_approval' ? 'border-stone-600 bg-stone-50 text-stone-700' : 'border-stone-200 text-stone-600 hover:border-stone-300'" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            <span class="text-[11px] font-semibold">No Approval</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Level settings (only when required) --}}
                                <div x-show="approvalForm.approval_mode === 'required'" x-transition>
                                    <div class="mb-4">
                                        <label class="form-label mb-1.5">Number of Approval Levels</label>
                                        <input type="number" x-model="approvalForm.levels_count" @change="adjustLevels()" min="1" max="20" class="form-input w-32 font-semibold">
                                    </div>

                                    <template x-for="(level, idx) in approvalForm.levels" :key="idx">
                                        <div class="mb-4 p-4 bg-stone-50 border border-stone-200 rounded-xl" x-show="idx < parseInt(approvalForm.levels_count)">
                                            <div class="flex items-center gap-2 mb-3">
                                                <div class="w-6 h-6 rounded-full bg-red-700 text-white flex items-center justify-center text-[10px] font-bold" x-text="idx + 1"></div>
                                                <input type="text" x-model="level.name" placeholder="Level name (e.g. Manager Approval)" class="flex-1 text-xs font-semibold bg-transparent border-none outline-none text-stone-800 placeholder-stone-400 p-0">
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div><label class="form-label">Approval Type</label><select x-model="level.approval_type" class="form-input"><option value="any_one">Any One Can Approve</option><option value="all_must">All Must Approve</option></select></div>
                                                <div><label class="form-label">Notify Via</label><select x-model="level.notify_via" class="form-input"><option value="email">Email</option><option value="sms">SMS</option><option value="both">Both</option></select></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label mb-1.5">Approvers</label>
                                                <div class="flex flex-wrap gap-1.5 mb-2">
                                                    <template x-for="(uid, aidx) in level.approver_ids" :key="aidx">
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-50 text-red-700 text-[10px] font-semibold">
                                                            <span x-text="getUserName(uid)"></span>
                                                            <button @click="level.approver_ids.splice(aidx, 1)" class="hover:text-red-900"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                        </span>
                                                    </template>
                                                </div>
                                                <select @change="addApprover(idx, $event.target.value); $event.target.value=''" class="form-input text-xs">
                                                    <option value="">+ Add approver...</option>
                                                    <template x-for="u in availableUsers" :key="u.id">
                                                        <option :value="u.id" x-text="u.name + ' (' + u.email + ')'" :disabled="level.approver_ids.includes(u.id)"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div><label class="form-label">Outstanding Time (hours)</label><input type="number" x-model="level.outstanding_hours" min="1" placeholder="24" class="form-input"></div>
                                                <div><label class="form-label">Auto-reject after (days)</label><input type="number" x-model="level.auto_reject_days" min="1" placeholder="7" class="form-input"></div>
                                            </div>
                                            <div class="p-3 bg-white border border-stone-200 rounded-lg space-y-3">
                                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" x-model="level.require_signature" class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700"><span class="text-xs font-medium text-stone-700">Require digital signature to approve</span></label>

                                                <div x-show="level.require_signature" x-transition class="pl-5 space-y-3">
                                                    <p class="text-[10px] text-stone-400">Approver must draw or upload a signature. A signing link will be sent via email.</p>

                                                    {{-- Per-approver signature status --}}
                                                    <div class="space-y-2">
                                                        <template x-for="(uid, aidx) in level.approver_ids" :key="'sig-'+uid">
                                                            <div class="flex items-center justify-between gap-2 p-2 bg-stone-50 rounded-lg">
                                                                <div class="flex items-center gap-2 min-w-0">
                                                                    <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                                                                        :class="getUserSignaturePath(uid) ? 'bg-green-100' : 'bg-stone-200'">
                                                                        <svg x-show="getUserSignaturePath(uid)" class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                                        <svg x-show="!getUserSignaturePath(uid)" class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                                    </div>
                                                                    <span class="text-[11px] font-medium text-stone-700 truncate" x-text="getUserName(uid)"></span>
                                                                    <span x-show="getUserSignaturePath(uid)" class="text-[9px] text-green-600 font-semibold shrink-0">Signed</span>
                                                                    <span x-show="!getUserSignaturePath(uid)" class="text-[9px] text-stone-400 shrink-0">Not signed</span>
                                                                </div>
                                                                <div class="flex items-center gap-1.5 shrink-0">
                                                                    <button x-show="getUserSignaturePath(uid)" type="button" @click="openSignatureModal(uid)" class="text-[10px] text-green-700 hover:text-green-800 font-semibold transition-colors">
                                                                        View
                                                                    </button>
                                                                    <span x-show="getUserSignaturePath(uid)" class="text-stone-200">|</span>
                                                                    <label class="text-[10px] text-blue-600 hover:text-blue-800 font-semibold cursor-pointer transition-colors">
                                                                        Upload
                                                                        <input type="file" accept="image/*" @change="uploadSignatureFor(uid, $event)" class="hidden">
                                                                    </label>
                                                                    <span class="text-stone-200">|</span>
                                                                    <button type="button" @click="sendSignatureLink(uid)" class="text-[10px] text-red-700 hover:text-red-800 font-semibold transition-colors">
                                                                        Send Link
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <p x-show="level.approver_ids.length === 0" class="text-[10px] text-stone-400 italic">Add approvers above to manage their signatures.</p>
                                                </div>
                                            </div>
                                            <div class="p-3 bg-white border border-stone-200 rounded-lg mt-2">
                                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" x-model="level.escalation_enabled" class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700"><span class="text-xs font-medium text-stone-700">Enable escalation</span></label>
                                                <div x-show="level.escalation_enabled" class="mt-2 pl-5"><label class="form-label">Escalate after (hours)</label><input type="number" x-model="level.escalation_hours" min="1" placeholder="48" class="form-input w-32"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="approvalForm.approval_mode === 'auto_approved'" class="p-4 bg-green-50 border border-green-200 rounded-xl">
                                    <div class="flex items-center gap-2 text-green-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><p class="text-xs font-semibold">Documents will be automatically approved upon submission.</p></div>
                                </div>
                                <div x-show="approvalForm.approval_mode === 'no_approval'" class="p-4 bg-stone-50 border border-stone-200 rounded-xl">
                                    <div class="flex items-center gap-2 text-stone-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg><p class="text-xs font-semibold">No approval workflow. Documents go directly to approved on submit.</p></div>
                                </div>
                            </div>

                            <div x-show="!approvalForm.is_enabled" class="p-4 bg-stone-50 border border-stone-200 rounded-xl">
                                <div class="flex items-center gap-2 text-stone-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <p class="text-xs font-semibold">Digital approval is disabled. Enable it above to configure approval workflow.</p>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-stone-100">
                                <div x-show="approvalSaved" x-transition class="flex items-center gap-1.5 text-xs text-green-700 font-semibold" style="display:none"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Saved</div>
                                <button @click="saveApproval()" :disabled="approvalSaving" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                                    <svg x-show="approvalSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <svg x-show="!approvalSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Approval Settings
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- Signature Preview Modal --}}
    <div x-show="sigModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="sigModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xs p-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-stone-800">Digital Signature</h3>
                <button @click="sigModal.open = false" class="w-6 h-6 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-[11px] text-stone-500 mb-2" x-text="sigModal.userName"></p>
            <div class="border border-stone-200 rounded-xl overflow-hidden bg-stone-50 p-3 flex items-center justify-center" style="height:120px;">
                <img :src="sigModal.url" alt="Signature" class="max-w-full max-h-full object-contain" x-show="sigModal.url">
            </div>
            <div class="mt-3 flex items-center justify-end gap-2">
                <label class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Re-upload
                    <input type="file" accept="image/*" @change="reuploadFromModal($event)" class="hidden">
                </label>
                <button @click="sigModal.open = false" class="px-2.5 py-1.5 text-[11px] font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Close</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function numberingPage() {
    return {
        activeDoc: '{{ $documentTypes->first()?->key ?? "invoice" }}',
        search: '',
        sigModal: { open: false, url: '', userName: '', userId: null },
        init() {},
    };
}

function docSettings(settingId, setting, defaultPrefix, docType, companyId, approvalData, allUsers, digitalApprovalEnabled) {
    const defaultLevel = { name: '', approver_ids: [], approval_type: 'any_one', notify_via: 'email', outstanding_hours: 24, auto_reject_days: 7, require_signature: false, escalation_enabled: false, escalation_hours: 48 };
    return {
        settingId,
        saving: false,
        saved: false,
        approvalSaving: false,
        approvalSaved: false,
        preview: '',
        subTab: 'numbering',
        availableUsers: allUsers || [],
        form: {
            prefix:          (setting && setting.prefix !== '' && setting.prefix !== null) ? setting.prefix : (defaultPrefix ?? ''),
            suffix:          setting?.suffix ?? '',
            next_number:     setting?.next_number ?? 1,
            pad_length:      String(setting?.pad_length ?? 4),
            reset_frequency: setting?.reset_frequency ?? 'yearly',
            include_date:    setting?.include_date ?? false,
            date_format:     setting?.date_format ?? 'YYYY-MM',
            separator:       (setting && setting.separator !== null && setting.separator !== undefined) ? setting.separator : '/',
        },
        approvalForm: {
            is_enabled:    approvalData?.is_enabled ?? digitalApprovalEnabled ?? false,
            approval_mode: approvalData?.approval_mode ?? 'no_approval',
            levels_count:  approvalData?.levels_count ?? 1,
            levels: approvalData?.levels && approvalData.levels.length > 0
                ? approvalData.levels.map(l => ({...defaultLevel, ...l, approver_ids: l.approver_ids || []}))
                : [{ ...defaultLevel, name: 'Level 1 Approval' }],
        },

        initDoc() {
            if (!this.form.prefix && defaultPrefix) this.form.prefix = defaultPrefix;
            this.updatePreview();
            // Ensure levels array matches levels_count
            this.adjustLevels();
        },

        updatePreview() {
            const num = String(this.form.next_number || 1).padStart(parseInt(this.form.pad_length) || 4, '0');
            const sep = this.form.separator !== undefined ? this.form.separator : '/';
            const parts = [];
            if (this.form.prefix) parts.push(this.form.prefix);
            if (this.form.include_date) {
                const now = new Date(), yyyy = now.getFullYear(), mm = String(now.getMonth()+1).padStart(2,'0'), dd = String(now.getDate()).padStart(2,'0');
                parts.push((this.form.date_format||'YYYY-MM').replace('YYYY',yyyy).replace('MM',mm).replace('DD',dd));
            }
            parts.push(num);
            if (this.form.suffix) parts.push(this.form.suffix);
            this.preview = parts.join(sep);
        },

        getUserName(id) {
            const u = this.availableUsers.find(u => u.id == id);
            return u ? u.name : `User #${id}`;
        },

        getUserSignaturePath(id) {
            const u = this.availableUsers.find(u => u.id == id);
            return u && u.signature_path ? u.signature_path : null;
        },

        getUserSignatureUrl(id) {
            const path = this.getUserSignaturePath(id);
            return path ? '/storage/' + path : null;
        },

        openSignatureModal(uid) {
            const url = this.getUserSignatureUrl(uid);
            const name = this.getUserName(uid);
            // Access the parent numberingPage component's sigModal
            const root = document.querySelector('[x-data*="numberingPage"]');
            if (root && root._x_dataStack) {
                const page = root._x_dataStack[0];
                page.sigModal = { open: true, url: url, userName: name, userId: uid };
            }
        },

        async reuploadFromModal(event) {
            const root = document.querySelector('[x-data*="numberingPage"]');
            if (!root || !root._x_dataStack) return;
            const page = root._x_dataStack[0];
            const uid = page.sigModal.userId;
            if (!uid) return;
            await this.uploadSignatureFor(uid, event);
            // Refresh the modal image
            const u = this.availableUsers.find(u => u.id == uid);
            if (u && u.signature_path) {
                page.sigModal.url = '/storage/' + u.signature_path + '?t=' + Date.now();
            }
        },

        addApprover(levelIdx, userId) {
            if (!userId) return;
            const uid = parseInt(userId);
            if (!this.approvalForm.levels[levelIdx].approver_ids.includes(uid)) {
                this.approvalForm.levels[levelIdx].approver_ids.push(uid);
            }
        },

        adjustLevels() {
            const count = parseInt(this.approvalForm.levels_count) || 1;
            // Add levels if needed
            while (this.approvalForm.levels.length < count) {
                this.approvalForm.levels.push({ ...defaultLevel, name: `Level ${this.approvalForm.levels.length + 1} Approval` });
            }
            // Trim excess levels (keep data but don't show)
        },

        async uploadSignatureFor(userId, event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const res = await fetch('/settings/numbering/upload-signature', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ user_id: userId, signature: e.target.result }),
                    });
                    const json = await res.json();
                    if (json.success) {
                        // Update local user data to reflect new signature
                        const u = this.availableUsers.find(u => u.id == userId);
                        if (u) u.signature_path = json.path || ('signatures/users/' + userId + '_updated.png');
                        this._toast('Signature uploaded for ' + this.getUserName(userId));
                    }
                    else { this._toast(json.message || 'Upload failed', 'error'); }
                } catch (err) { this._toast('Network error', 'error'); }
            };
            reader.readAsDataURL(file);
            event.target.value = '';
        },

        async sendSignatureLink(userId) {
            try {
                const res = await fetch('/settings/numbering/send-signature-link', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ user_id: userId }),
                });
                const json = await res.json();
                if (json.success) { this._toast('Signing link sent to ' + this.getUserName(userId)); }
                else { this._toast(json.message || 'Failed to send', 'error'); }
            } catch (err) { this._toast('Network error', 'error'); }
        },

        _toast(message, type = 'success') {
            const el = document.createElement('div');
            const isErr = type === 'error';
            el.style.cssText = `position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;padding:.6rem 1rem;border-radius:.75rem;font-size:.75rem;font-weight:500;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.1);background:${isErr ? '#fef2f2' : '#f0fdf4'};border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};color:${isErr ? '#991b1b' : '#166534'};`;
            el.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3500);
        },

        async saveNumbering() {
            if (!this.settingId) return;
            this.saving = true;
            try {
                const res = await fetch(`/settings/numbering/${this.settingId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, include_date: this.form.include_date ? 1 : 0 }),
                });
                const json = await res.json();
                if (json.success) {
                    if (json.preview) this.preview = json.preview;
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 3000);
                }
            } finally { this.saving = false; }
        },

        async saveApproval() {
            this.approvalSaving = true;
            try {
                const count = parseInt(this.approvalForm.levels_count) || 1;
                const levelsToSave = this.approvalForm.levels.slice(0, count);
                const res = await fetch('/settings/numbering/approval', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        company_id: companyId,
                        document_type: docType,
                        is_enabled: this.approvalForm.is_enabled ? 1 : 0,
                        approval_mode: this.approvalForm.approval_mode,
                        levels_count: count,
                        levels: levelsToSave,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.approvalSaved = true;
                    setTimeout(() => { this.approvalSaved = false; }, 3000);
                }
            } finally { this.approvalSaving = false; }
        },
    };
}
</script>
@endpush
