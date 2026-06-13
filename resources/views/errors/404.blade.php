@extends('errors.layout')

@section('title', '404 — Page Not Found')
@section('code', '404')
@section('heading', 'Page Not Found')

@section('icon')
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
@endsection

@section('description')
    The page you're looking for doesn't exist or may have been moved.
    Double-check the URL or head back to the dashboard.
@endsection

@section('actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
       class="btn-ghost">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Go Back
    </a>
    <a href="{{ url('/') }}" class="btn-primary">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Back to Dashboard
    </a>
@endsection
