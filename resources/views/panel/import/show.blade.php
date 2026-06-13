@extends('layouts.app')

@section('title', 'Import Job #' . $job->id)
@section('page-title', 'Import Job #' . $job->id)

@section('content')
    <div class="mx-auto space-y-6">

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('master.import.index', ['tab' => 'history']) }}"
                class="inline-flex items-center px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg font-medium transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Import History
            </a>

            @if ($job->failed_rows > 0)
                <a href="{{ route('master.import.errors', $job) }}"
                    class="inline-flex items-center px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Error Report
                </a>
            @endif
        </div>

        {{-- Job Summary --}}
        <div class="bg-white border border-stone-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold mb-6">Import Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <div class="text-sm text-stone-500">Table</div>
                    <div class="font-medium">{{ $job->data_type }}</div>
                </div>
                <div>
                    <div class="text-sm text-stone-500">Source</div>
                    <div class="font-medium">{{ strtoupper($job->source_type) }}</div>
                </div>
                <div>
                    <div class="text-sm text-stone-500">Status</div>
                    <div>
                        @if ($job->status === 'completed')
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Completed
                            </span>
                        @elseif($job->status === 'failed')
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                Failed
                            </span>
                        @elseif($job->status === 'partial')
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                Partial
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                                {{ ucfirst($job->status) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-sm text-stone-500">Started</div>
                    <div class="font-medium">{{ $job->started_at ? $job->started_at->format('Y-m-d H:i:s') : '-' }}</div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <div class="text-center p-4 bg-stone-50 rounded-lg">
                    <div class="text-2xl font-bold text-stone-700">{{ $job->total_rows }}</div>
                    <div class="text-sm text-stone-500">Total Rows</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-700">{{ $job->success_rows }}</div>
                    <div class="text-sm text-green-600">Success</div>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-700">{{ $job->failed_rows }}</div>
                    <div class="text-sm text-red-600">Failed</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-700">{{ $job->skipped_rows }}</div>
                    <div class="text-sm text-yellow-600">Skipped</div>
                </div>
            </div>
        </div>

        {{-- Row Details --}}
        <div class="bg-white border border-stone-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold mb-4">Row Details</h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-stone-50 border-b border-stone-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Row #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Entity ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Error</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse($rows as $row)
                            <tr class="hover:bg-stone-50">
                                <td class="px-4 py-3 text-sm">{{ $row->row_number }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($row->status === 'success')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Success
                                        </span>
                                    @elseif($row->status === 'failed')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            Failed
                                        </span>
                                    @elseif($row->status === 'skipped')
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                            Skipped
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-700">
                                            {{ ucfirst($row->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $row->action_taken ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->entity_id ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-red-600">{{ $row->error_message ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <button onclick="showRowData({{ json_encode($row->mapped_data ?? $row->raw_data) }})"
                                        class="text-red-700 hover:text-red-800">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-500">
                                    No rows found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="pagination-wrapper">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>

    </div>

    {{-- Modal for row data --}}
    <div id="row-data-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Row Data</h3>
                <button onclick="closeRowDataModal()" class="text-stone-500 hover:text-stone-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <pre id="row-data-content" class="bg-stone-50 p-4 rounded-lg text-sm overflow-x-auto"></pre>
        </div>
    </div>

    <script>
        function showRowData(data) {
            document.getElementById('row-data-content').textContent = JSON.stringify(data, null, 2);
            document.getElementById('row-data-modal').classList.remove('hidden');
        }

        function closeRowDataModal() {
            document.getElementById('row-data-modal').classList.add('hidden');
        }
    </script>

    <style>
        /* Custom pagination styling to match red theme */
        .pagination-wrapper nav {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination-wrapper .flex {
            width: 100%;
        }

        /* Style pagination links */
        .pagination-wrapper nav span,
        .pagination-wrapper nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            height: 2.5rem;
            padding: 0.5rem;
            margin: 0 0.125rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        /* Default link style */
        .pagination-wrapper nav a {
            background-color: #f5f5f4;
            color: #57534e;
            border: 1px solid #e7e5e4;
        }

        .pagination-wrapper nav a:hover {
            background-color: #b91c1c;
            color: white;
            border-color: #b91c1c;
        }

        /* Active page style */
        .pagination-wrapper nav span[aria-current="page"] {
            background-color: #b91c1c;
            color: white;
            border: 1px solid #b91c1c;
            font-weight: 600;
        }

        /* Disabled state (dots) */
        .pagination-wrapper nav span[aria-disabled="true"] {
            background-color: transparent;
            color: #a8a29e;
            border: none;
            cursor: default;
        }

        /* Previous/Next buttons */
        .pagination-wrapper nav a[rel="prev"],
        .pagination-wrapper nav a[rel="next"] {
            background-color: #b91c1c;
            color: white;
            border-color: #b91c1c;
        }

        .pagination-wrapper nav a[rel="prev"]:hover,
        .pagination-wrapper nav a[rel="next"]:hover {
            background-color: #991b1b;
            border-color: #991b1b;
        }

        /* Disabled Previous/Next */
        .pagination-wrapper nav span[aria-disabled="true"][rel="prev"],
        .pagination-wrapper nav span[aria-disabled="true"][rel="next"] {
            background-color: #e7e5e4;
            color: #a8a29e;
            border-color: #e7e5e4;
            cursor: not-allowed;
        }
    </style>
@endsection
