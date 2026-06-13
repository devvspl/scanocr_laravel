{{-- Digital Signatures Section — include in show pages --}}
{{-- Expects: $approvalLogs (collection of ApprovalLog with signature_path) --}}
@php
    $signedLogs = ($approvalLogs ?? collect())->filter(fn($log) => $log->signature_path && $log->action === 'approved');
@endphp

@if($signedLogs->count() > 0)
<div class="px-5 py-4 border-t border-stone-100">
    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-3">Digital Signatures</p>
    <div class="flex flex-wrap gap-4">
        @foreach($signedLogs as $log)
        <div class="text-center">
            <div class="border border-stone-200 rounded-lg p-2 bg-stone-50" style="width:140px;height:70px;display:flex;align-items:center;justify-content:center;">
                <img src="{{ asset('storage/' . $log->signature_path) }}" alt="Signature" style="max-width:100%;max-height:100%;object-fit:contain;">
            </div>
            <p class="text-[10px] font-semibold text-stone-700 mt-1.5">{{ $log->user->name ?? 'Unknown' }}</p>
            <p class="text-[9px] text-stone-400">{{ $log->level_name ?? 'Level ' . $log->level }}</p>
            @if($log->signed_at)
            <p class="text-[9px] text-stone-400">{{ $log->signed_at->format('d M Y, h:i A') }}</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
