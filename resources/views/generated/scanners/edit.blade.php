@extends('layouts.app')
@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
    <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-stone-800">Edit Scanner</h3>
            <p class="text-xs text-stone-400 mt-0.5">Update the record details.</p>
        </div>
        <a href="{{ route('generated.scanners.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back</a>
    </div>
    <form method="POST" action="{{ route('generated.scanners.update', $scanner) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="p-6">
            @if($errors->any())
            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl">Please fix the errors below.</div>
            @endif
            <div class="grid grid-cols-3 gap-5">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ $scanner->title }}" placeholder="Document Title" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('title') border-red-400 bg-red-50 @enderror">
                    @error('title')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Document No <span class="text-red-500">*</span></label>
                    <input type="text" name="document_no" value="{{ $scanner->document_no }}" placeholder="Document No" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('document_no') border-red-400 bg-red-50 @enderror">
                    @error('document_no')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Date <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="document_date" value="{{ $scanner->document_date ?? date('Y-m-d\TH:i') }}" placeholder="Document Date" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('document_date') border-red-400 bg-red-50 @enderror">
                    @error('document_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Type <span class="text-red-500">*</span></label>
                    <select name="document_type" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('document_type') border-red-400 bg-red-50 @enderror"><option value="">-- Select --</option>
                        @isset($document_type_options)
                            @foreach($document_type_options as $val => $lab)
                                <option value="{{ $val }}" {{ ($scanner->document_type ?? '') == $val ? 'selected' : '' }}>{{ $lab }}</option>
                            @endforeach
                        @endisset
</select>
                    @error('document_type')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Remarks</label>
                    <textarea name="remarks" rows="4" placeholder="Remarks" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('remarks') border-red-400 bg-red-50 @enderror resize-none">{{ $scanner->remarks }}</textarea>
                    @error('remarks')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Upload Scan Copy <span class="text-red-500">*</span></label>
                    @if(!empty($scanner->upload_scan_copy))
                    <div class="mb-3 space-y-1.5">
                        @foreach((array)$scanner->upload_scan_copy as $__fi => $__fp)
                        <div class="flex items-center gap-2 px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg">
                            <svg class="w-4 h-4 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span class="text-xs text-stone-600 truncate flex-1">{{ basename($__fp) }}</span>
                            <label class="flex items-center gap-1 text-xs text-red-600 cursor-pointer shrink-0">
                                <input type="checkbox" name="upload_scan_copy_remove[]" value="{{ $__fp }}" class="w-3.5 h-3.5 rounded border-stone-300 text-red-600">
                                Remove
                            </label>
                            <input type="hidden" name="upload_scan_copy_keep[]" value="{{ $__fp }}">
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <input type="file" name="upload_scan_copy[]" multiple class="w-full text-sm text-stone-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-red-800 file:text-white file:text-xs file:font-medium">
                    <p class="mt-1 text-xs text-stone-400">Select one or more files. Existing files are kept unless removed.</p>
                    @error('upload_scan_copy')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">other</label>
                    <select name="other" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('other') border-red-400 bg-red-50 @enderror"><option value="">-- Select --</option><option value="1" {{ ($scanner->other ?? '') == '1' ? 'selected' : '' }}>Yes</option><option value="0" {{ ($scanner->other ?? '') == '0' ? 'selected' : '' }}>No</option></select>
                    @error('other')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex items-center justify-end gap-3">
            <a href="{{ route('generated.scanners.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-medium text-stone-600 bg-white border border-stone-300 hover:bg-stone-50 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Update Record</button>
        </div>
    </form>
</div>
@endsection
