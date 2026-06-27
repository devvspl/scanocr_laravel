@extends('layouts.app')
@section('title', 'Not Available for Editing')
@section('page-title', 'Not Available for Editing')

@section('content')
<div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="w-20 h-20 rounded-2xl bg-amber-50 flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                     m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-stone-800 mb-2">Not Available for Editing</h1>
    <p class="text-stone-500 text-sm max-w-sm mb-8 leading-relaxed">
        This scan entry is not currently available for editing.<br>
        It must be rejected back with edit permission before it can be modified.
    </p>
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('workflow.punching.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 text-sm font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Go Back
        </a>
        <a href="{{ route('workflow.punching.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Back to Punching List
        </a>
    </div>
</div>
@endsection
