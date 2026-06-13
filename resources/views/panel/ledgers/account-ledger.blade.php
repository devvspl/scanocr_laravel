@extends('layouts.app')

@section('title', 'Account Ledger - ' . $account->name)
@section('page-title', 'Account Ledger')

@section('breadcrumb')
    <span>Accounting</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('ledgers.accounts') }}" class="hover:text-stone-600 transition-colors">Accounts</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-stone-600">{{ $account->name }}</span>
@endsection

@section('content')
<div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
    
    {{-- Header --}}
    <div class="border-b border-stone-200 bg-stone-50 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-stone-800">{{ $account->name }}</h2>
                <p class="text-sm text-stone-500 mt-1">Account Code: {{ $account->code }} @if($account->group) | Group: {{ $account->group->name }}@endif</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                        class="px-3 py-1.5 text-sm border border-stone-300 rounded-lg">
                    <span class="text-sm text-stone-500">to</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                        class="px-3 py-1.5 text-sm border border-stone-300 rounded-lg">
                    <button type="submit" class="px-4 py-1.5 text-sm font-semibold text-white bg-red-800 hover:bg-red-900 rounded-lg transition-colors">
                        Filter
                    </button>
                </form>
                <a href="{{ route('ledgers.account.pdf', ['account' => $account->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                    class="px-4 py-1.5 text-sm font-semibold text-stone-700 border border-stone-300 hover:bg-stone-50 rounded-lg transition-colors">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    {{-- Ledger Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-100 border-b border-stone-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-stone-700">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-stone-700">Voucher Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-stone-700">Voucher No.</th>
                    <th class="px-4 py-3 text-left font-semibold text-stone-700">Particulars</th>
                    <th class="px-4 py-3 text-right font-semibold text-stone-700">Debit (₹)</th>
                    <th class="px-4 py-3 text-right font-semibold text-stone-700">Credit (₹)</th>
                    <th class="px-4 py-3 text-right font-semibold text-stone-700">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening Balance --}}
                <tr class="border-b border-stone-200 bg-blue-50">
                    <td class="px-4 py-3 text-stone-700" colspan="4">
                        <span class="font-semibold">Opening Balance</span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        @if($openingBalance['balance_type'] === 'Dr')
                            {{ number_format($openingBalance['balance'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        @if($openingBalance['balance_type'] === 'Cr')
                            {{ number_format($openingBalance['balance'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        {{ number_format($openingBalance['balance'], 2) }} {{ $openingBalance['balance_type'] }}
                    </td>
                </tr>

                {{-- Entries --}}
                @forelse($entries as $entry)
                <tr class="border-b border-stone-100 hover:bg-stone-50 transition-colors">
                    <td class="px-4 py-3 text-stone-700">
                        {{ $entry->entry_date->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-stone-700">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-stone-100 text-stone-700">
                            {{ ucwords(str_replace('_', ' ', $entry->voucher_type)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-stone-700 font-mono text-xs">
                        {{ $entry->voucher_number }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-stone-800 font-medium">{{ $entry->narration }}</div>
                        @if($entry->party)
                            <div class="text-xs text-stone-500">{{ $entry->party->display_name ?? $entry->party->name }}</div>
                        @endif
                        @if($entry->description)
                            <div class="text-xs text-stone-500">{{ $entry->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium {{ $entry->debit > 0 ? 'text-red-700' : 'text-stone-400' }}">
                        {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium {{ $entry->credit > 0 ? 'text-green-700' : 'text-stone-400' }}">
                        {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-stone-800">
                        {{ number_format($entry->running_balance, 2) }} {{ $entry->balance_type }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-stone-500">
                        No ledger entries found for the selected period.
                    </td>
                </tr>
                @endforelse

                {{-- Closing Balance --}}
                @if($entries->isNotEmpty())
                <tr class="border-t-2 border-stone-300 bg-stone-50">
                    <td class="px-4 py-3 text-stone-700" colspan="4">
                        <span class="font-bold">Closing Balance</span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        @if($closingBalance['balance_type'] === 'Dr')
                            {{ number_format($closingBalance['balance'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        @if($closingBalance['balance_type'] === 'Cr')
                            {{ number_format($closingBalance['balance'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-stone-800">
                        {{ number_format($closingBalance['balance'], 2) }} {{ $closingBalance['balance_type'] }}
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

</div>
@endsection
