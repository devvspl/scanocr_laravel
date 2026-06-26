@extends('layouts.app')
@section('title', 'View Punched Detail')
@section('page-title', 'View Punched Detail')

@push('head')
<style>
.view-container{max-width:1400px;margin:0 auto;padding:1rem;background:#fff;min-height:calc(100vh - 80px)}
.view-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:1rem;border-bottom:2px solid #e7e5e4}
.view-header h2{font-size:1.1rem;font-weight:700;color:#292524;margin:0}
.view-header .doc-badge{background:#7f1d1d;color:#fff;padding:.3rem .8rem;border-radius:.5rem;font-size:.75rem;font-weight:600}
.file-viewer-section{display:flex;gap:1rem;margin-bottom:1.5rem}
.file-tabs-wrapper{flex:0 0 280px;display:flex;flex-direction:column;gap:.5rem;background:#fafaf9;padding:.8rem;border-radius:.75rem;border:2px solid #e7e5e4;max-height:600px;overflow-y:auto}
.file-tab{padding:.5rem .75rem;background:#fff;border:1px solid #d6d3d1;border-radius:.5rem;cursor:pointer;font-size:.7rem;transition:all .2s;display:flex;align-items:center;gap:.4rem}
.file-tab:hover{background:#f5f5f4;border-color:#7f1d1d}
.file-tab.active{background:#7f1d1d;color:#fff;border-color:#7f1d1d}
.file-tab .icon{font-size:.9rem}
.file-viewer{flex:1;border:2px solid #e7e5e4;border-radius:.75rem;overflow:hidden;background:#292524;min-height:600px;display:flex;align-items:center;justify-content:center}
.file-viewer iframe,.file-viewer img{width:100%;height:600px;border:none}
.file-viewer img{object-fit:contain;background:#fff}
.form-wrapper{background:#fafaf9;padding:1.25rem;border-radius:.75rem;border:2px solid #e7e5e4}
.form-wrapper h3{font-size:.85rem;font-weight:700;color:#7f1d1d;margin:0 0 1rem;text-transform:uppercase;letter-spacing:.03em}
.f-row{display:flex;gap:.6rem;margin-bottom:.6rem;flex-wrap:wrap}
.f-row.cols-1{flex-direction:column}
.f-row.cols-2>*{flex:1;min-width:calc(50% - .3rem)}
.f-row.cols-3>*{flex:1;min-width:calc(33.333% - .4rem)}
.f-group{flex:1;min-width:180px}
.f-group label{display:block;font-size:.65rem;font-weight:600;color:#57534e;margin-bottom:.2rem}
.f-input,.f-select{width:100%;background:#fff;border:1px solid #d6d3d1;border-radius:.5rem;padding:.3rem .6rem;font-size:.7rem;color:#292524;outline:none;transition:border-color .15s;pointer-events:none;opacity:0.8}
textarea.f-input{resize:vertical;min-height:60px}
.items-table{width:100%;border-collapse:collapse;font-size:.65rem}
.items-table thead{background:#f5f5f4;position:sticky;top:0;z-index:1}
.items-table th{padding:.4rem .5rem;text-align:left;font-weight:600;color:#57534e;border-bottom:2px solid #e7e5e4;white-space:nowrap}
.items-table td{padding:.35rem .5rem;border-bottom:1px solid #e7e5e4}
.items-table input,.items-table select{width:100%;padding:.25rem .4rem;border:1px solid #d6d3d1;border-radius:.35rem;font-size:.65rem;background:#fff;pointer-events:none;opacity:0.8}
.btn-back{display:inline-flex;align-items:center;gap:.3rem;padding:.5rem 1rem;background:#fff;border:1px solid #d6d3d1;border-radius:.5rem;font-size:.75rem;font-weight:600;color:#57534e;text-decoration:none;transition:all .2s}
.btn-back:hover{background:#f5f5f4;border-color:#7f1d1d;color:#7f1d1d}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.8rem;margin-bottom:1.5rem;padding:1rem;background:#fef3c7;border-radius:.75rem;border:2px solid #fde047}
.info-item{display:flex;flex-direction:column}
.info-label{font-size:.6rem;font-weight:600;color:#78350f;text-transform:uppercase;letter-spacing:.03em}
.info-value{font-size:.75rem;font-weight:600;color:#292524;margin-top:.15rem}
</style>
@endpush

@section('content')
<div class="view-container">
    {{-- Header --}}
    <div class="view-header">
        <div>
            <h2>Punched Entry Details — #{{ $scanData->Scan_Id }}</h2>
            <p style="font-size:.7rem;color:#78716c;margin:.3rem 0 0">Document Type: <span class="doc-badge">{{ $scanData->doc_type_label }}</span></p>
        </div>
        <a href="{{ route('workflow.punching.index') }}" class="btn-back">
            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to List
        </a>
    </div>

    {{-- Scan Info --}}
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Scan ID</span>
            <span class="info-value">#{{ $scanData->Scan_Id }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Document Name</span>
            <span class="info-value">{{ $scanData->Document_name ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Scan Date</span>
            <span class="info-value">{{ $scanData->Scan_Date ? \Carbon\Carbon::parse($scanData->Scan_Date)->format('d M Y') : '—' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Punch Date</span>
            <span class="info-value">{{ $punchDetail && $punchDetail->Created_Date ? \Carbon\Carbon::parse($punchDetail->Created_Date)->format('d M Y H:i') : '—' }}</span>
        </div>
    </div>

    {{-- File Viewer --}}
    <div class="file-viewer-section">
        {{-- File Tabs --}}
        <div class="file-tabs-wrapper">
            <h4 style="font-size:.75rem;font-weight:700;color:#292524;margin:0 0 .5rem">📄 Scanned Files</h4>
            @php
                $files = [];
                // Main file
                if ($scanData->File && $scanData->File_Location) {
                    // Check if File_Location is already a full URL (S3) or a relative path
                    if (str_starts_with($scanData->File_Location, 'http://') || str_starts_with($scanData->File_Location, 'https://')) {
                        $mainUrl = $scanData->File_Location;
                    } else {
                        $mainUrl = asset('storage/' . $scanData->File_Location . '/' . $scanData->File);
                    }
                    $files[] = [
                        'name' => 'Main Document',
                        'url' => $mainUrl,
                        'ext' => strtolower($scanData->File_Ext ?? 'pdf')
                    ];
                }
                // Support files
                $supportFiles = DB::table('support_file')->where('Scan_Id', $scanData->Scan_Id)->get();
                foreach ($supportFiles as $sf) {
                    if ($sf->File && $sf->File_Location) {
                        // Check if File_Location is already a full URL (S3) or a relative path
                        if (str_starts_with($sf->File_Location, 'http://') || str_starts_with($sf->File_Location, 'https://')) {
                            $supportUrl = $sf->File_Location;
                        } else {
                            $supportUrl = asset('storage/' . $sf->File_Location . '/' . $sf->File);
                        }
                        $files[] = [
                            'name' => 'Support ' . $sf->Support_Id,
                            'url' => $supportUrl,
                            'ext' => strtolower($sf->File_Ext ?? 'pdf')
                        ];
                    }
                }
            @endphp
            @foreach($files as $idx => $file)
                <div class="file-tab {{ $idx === 0 ? 'active' : '' }}" data-url="{{ $file['url'] }}" data-ext="{{ $file['ext'] }}">
                    <span class="icon">{{ $file['ext'] === 'pdf' ? '📄' : '🖼️' }}</span>
                    <span>{{ $file['name'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- File Display --}}
        <div class="file-viewer" id="fileViewer">
            @if(!empty($files))
                @if($files[0]['ext'] === 'pdf')
                    <iframe src="{{ $files[0]['url'] }}"></iframe>
                @else
                    <img src="{{ $files[0]['url'] }}" alt="Document">
                @endif
            @else
                <p style="color:#a8a29e;font-size:.8rem">No file available</p>
            @endif
        </div>
    </div>

    {{-- Punched Detail Form (Read-Only) --}}
    <div class="form-wrapper">
        <h3>📋 Punched Entry Details</h3>
        
        @if($punchDetail)
            @include('panel.workflow.punching.view-partials.' . $formPartial . '-view', [
                'scanData' => $scanData,
                'punchDetail' => $punchDetail,
            ])
        @else
            <p style="text-align:center;color:#a8a29e;font-size:.8rem;padding:2rem">No punched data available for this scan.</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    // File tab switching
    $('.file-tab').on('click', function(){
        $('.file-tab').removeClass('active');
        $(this).addClass('active');
        const url = $(this).data('url');
        const ext = $(this).data('ext');
        const $viewer = $('#fileViewer');
        $viewer.find('iframe, img').remove();
        
        if (ext === 'pdf') {
            $viewer.append(`<iframe src="${url}"></iframe>`);
        } else {
            $viewer.append(`<img src="${url}" alt="Document">`);
        }
    });
});
</script>
@endpush
