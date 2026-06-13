@php $config = $widget->config ?? []; @endphp
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden h-full flex flex-col">
    <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-stone-800">{{ $widget->title }}</h3>
        <div class="flex items-center gap-2">
            <div class="relative">
                <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="entries-search" class="pl-8 pr-3 py-1.5 border border-stone-200 rounded-lg text-xs w-44 focus:border-red-700 outline-none" placeholder="Search entries...">
            </div>
        </div>
    </div>
    <div class="overflow-x-auto flex-1">
        <table class="w-full text-xs">
            <thead>
                <tr id="entries-thead" class="bg-stone-50 border-b border-stone-200">
                    <th class="px-4 py-2.5 text-left font-semibold text-stone-500 w-10">#</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-stone-500">Entry</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-stone-500">Status</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-stone-500">Date</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-stone-500 w-16">Action</th>
                </tr>
            </thead>
            <tbody id="entries-table-body">
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-stone-400 text-xs">No entries yet</td>
                </tr>
            </tbody>
        </table>
    </div>
    {{-- Pagination --}}
    <div id="entries-pagination" class="px-4 py-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
        <span id="entries-info">Showing 0 entries</span>
        <div class="flex items-center gap-1" id="entries-pages"></div>
    </div>
</div>

{{-- View Entry Modal --}}
<div id="entry-modal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEntryModal()"></div>
    <div class="absolute inset-4 md:inset-y-8 md:inset-x-[15%] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-stone-800" id="modal-title">Entry Details</h3>
            <button onclick="closeEntryModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-stone-100 text-stone-400 hover:text-stone-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6" id="modal-body">
            <div class="text-center py-8 text-stone-400 text-sm">Loading...</div>
        </div>
    </div>
</div>
