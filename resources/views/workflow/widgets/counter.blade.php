@php $config = $widget->config ?? []; @endphp
<div class="bg-white rounded-xl border border-stone-200 p-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: {{ $config['color'] ?? '#7f1d1d' }}20;">
        <i class="{{ $config['icon'] ?? 'fa-solid fa-file' }} text-lg" style="color: {{ $config['color'] ?? '#7f1d1d' }};"></i>
    </div>
    <div>
        <p class="text-2xl font-bold text-stone-800" id="counter-value">0</p>
        <p class="text-xs text-stone-500">{{ $widget->title }}</p>
    </div>
</div>
