@php
    $pad = $depth * 20;
    $hasChildren = $group->children->isNotEmpty();
    $nodeId = 'node-' . $group->id;
@endphp

<div class="group-node" x-data="{ open: expandAll }" x-watch="expandAll" x-effect="open = expandAll">
    <div class="flex items-center gap-1.5 py-1.5 px-2 rounded-lg hover:bg-stone-50 transition-colors cursor-pointer group/row"
         style="padding-left: {{ $pad + 8 }}px">

        {{-- Toggle --}}
        @if($hasChildren)
        <button @click.stop="open = !open"
                class="w-4 h-4 flex items-center justify-center text-stone-400 hover:text-stone-700 shrink-0 transition-colors">
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        @else
        <span class="w-4 h-4 shrink-0"></span>
        @endif

        {{-- Icon --}}
        <span class="w-5 h-5 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 {{ $hasChildren ? 'text-red-700' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($hasChildren)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5l2 2h4a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h2z"/>
                @endif
            </svg>
        </span>

        {{-- Name --}}
        <span class="flex-1 text-sm text-stone-700 font-medium truncate">{{ $group->name }}</span>

        {{-- Nature badge --}}
        @php
            $natureColors = [
                'assets'      => 'bg-blue-50 text-blue-700',
                'liabilities' => 'bg-orange-50 text-orange-700',
                'income'      => 'bg-green-50 text-green-700',
                'expense'     => 'bg-red-50 text-red-700',
            ];
        @endphp
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide {{ $natureColors[$group->nature] ?? 'bg-stone-100 text-stone-500' }}">
            {{ $group->nature }}
        </span>

        {{-- Status dot --}}
        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $group->is_active ? 'bg-green-500' : 'bg-stone-300' }}"></span>

        {{-- Created by --}}
        @if($group->creator)
        <span class="hidden group-hover/row:inline-flex items-center gap-1 text-[10px] text-stone-400 shrink-0 whitespace-nowrap">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ $group->creator->name }}
        </span>
        @endif

        {{-- Actions --}}
        <div class="act-group opacity-0 group-hover/row:opacity-100 transition-opacity">
            <button type="button"
                    @click.stop="setEdit({{ $group->id }})"
                    class="act-btn act-edit" title="Edit">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            <form method="POST" action="{{ route('master.account-groups.destroy', $group) }}"
                  onsubmit="return confirm('Delete this group?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="act-btn act-delete" title="Delete">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Children --}}
    @if($hasChildren)
    <div x-show="open" x-collapse>
        @foreach($group->children as $child)
            @include('panel.accounts._group-node', ['group' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif
</div>
