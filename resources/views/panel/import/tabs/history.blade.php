<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-stone-50 border-b border-stone-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Table</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Source</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rows</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Started</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200">
            @forelse($jobs->take(10) as $job)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3 text-sm">{{ $job->id }}</td>
                <td class="px-4 py-3 text-sm font-medium">{{ $job->data_type }}</td>
                <td class="px-4 py-3 text-sm">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                        {{ strtoupper($job->source_type) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if($job->status === 'completed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Completed
                        </span>
                    @elseif($job->status === 'failed')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Failed
                        </span>
                    @elseif($job->status === 'partial')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            Partial
                        </span>
                    @elseif($job->status === 'processing')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            Processing
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                            Pending
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm">
                    <div class="flex gap-2 text-xs">
                        <span class="text-green-600">✓ {{ $job->success_rows }}</span>
                        <span class="text-red-600">✗ {{ $job->failed_rows }}</span>
                        <span class="text-yellow-600">⊘ {{ $job->skipped_rows }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-stone-500">
                    {{ $job->started_at ? $job->started_at->format('M d, H:i') : '-' }}
                </td>
                <td class="px-4 py-3 text-sm">
                    <a href="{{ route('master.import.show', $job) }}" class="text-red-700 hover:text-red-800 font-medium">
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-stone-500">
                    No import history yet. Start your first import above!
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($jobs->count() > 10)
<div class="mt-4 text-center">
    <a href="{{ route('master.import.index') }}?view=all" class="text-red-700 hover:text-red-800 font-medium">
        View All Imports →
    </a>
</div>
@endif
