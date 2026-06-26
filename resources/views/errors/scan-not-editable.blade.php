@extends('errors.layout')

@section('title', 'Not Available for Editing')
@section('code', '—')
@section('heading', 'Not Available for Editing')

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6V5m0 0L9 8m3-3l3 3M5 3a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2H5z"/>
    </svg>
@endsection

@section('description')
    This scan entry is not available for editing right now.
    It must be rejected back with edit permission before it can be modified.
    If you believe this is a mistake, please contact your administrator.
@endsection

@section('actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('workflow.punching.index') }}"
       class="btn-ghost">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Go Back
    </a>
    <a href="{{ route('workflow.punching.index') }}" class="btn-primary">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Back to Punching List
    </a>
@endsection
