@extends('layouts.app')
@section('title', 'Invoice Entry - Scan #' . $scanData->Scan_Id)
@section('page-title', 'Invoice Entry')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
<style>

        .select2-container--default .select2-selection--single {
            height: 24px;
            border: 1px solid #d6d3d1;
            border-radius: .5rem;
            background: #fafaf9;
            display: flex;
            align-items: center;
            min-height: 24px
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .75rem;
            color: #292524;
            padding-left: .75rem;
            line-height: 34px
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .5rem
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #a8a29e
        }

        .select2-container--default .select2-results__option {
            font-size: .75rem;
            padding: .4rem .75rem
        }

        .select2-container--default .select2-results__option--highlighted {
            background: #7f1d1d;
            color: #fff
        }

        .select2-search--dropdown .select2-search__field {
            font-size: .75rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            padding: .3rem .5rem
        }

        .select2-dropdown {
            border: 1px solid #d6d3d1;
            border-radius: .5rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08)
        }

        .select2-container--open .select2-selection--single {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)
        }
        .select2-container .select2-selection--single {
            height: 24px !important;
            border: 1px solid #d6d3d1 !important;
            border-radius: 0.5rem !important;
            background: #fafaf9 !important;
            padding: 0 !important
        }

        .select2-container .select2-selection--single .select2-selection__clear {
            background-color: transparent;
            border: none;
            font-size: smaller;
                color: #888888;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 26px;
            position: absolute;
            top: -4px;
            right: 1px;
            width: 20px;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 0 0 0 12px !important;
            line-height: 34px !important;
            font-size: 10px !important;
            color: #292524 !important
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
            right: 8px !important
        }

        .select2-container .select2-selection--single .select2-selection__arrow b {
            border-width: 4px 4px 0 4px !important;
            margin-top: -2px !important
        }.entry-grid{display:grid;grid-template-columns:1fr;height:calc(100vh - 120px)}
@media(min-width:1024px){.entry-grid{grid-template-columns:4fr 8fr}}
.file-panel{display:flex;flex-direction:column;border-right:1px solid #e7e5e4;overflow:hidden;background:#292524}
.file-tabs{display:flex;gap:0;padding:0 .5rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-wrap:wrap}
.file-tab{padding:.35rem .6rem;font-size:.6rem;font-weight:600;color:#78716c;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer}
.file-tab.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.file-viewer{flex:1;position:relative;min-height:300px}
.file-viewer iframe,.file-viewer img{position:absolute;inset:0;width:100%;height:100%;border:none;object-fit:contain;background:#292524}
</style>
<style>
.form-panel{display:flex;flex-direction:column;overflow-y:auto;padding:.75rem 1rem;background:#fff}
.f-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:.6rem}
.f-row.cols-1{grid-template-columns:1fr}
.f-row.cols-2{grid-template-columns:repeat(2,1fr)}
.f-row.cols-3{grid-template-columns:repeat(3,1fr)}
.f-group{min-width:0}
.f-group label{font-size:.6rem;font-weight:600;color:#78716c;text-transform:uppercase;display:block;margin-bottom:2px}
.f-group .hint{font-size:.55rem;color:#dc2626;display:block;margin-bottom:1px}
.f-input{height:28px;width:100%;padding:0 .5rem;font-size:.72rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524}
.f-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 2px rgba(127,29,29,.06)}
textarea.f-input{height:60px;resize:vertical;padding:.4rem .5rem}
.items-table{width:100%;border-collapse:collapse;font-size:.7rem}
.items-table th{background:#7f1d1d;color:#fff;font-size:.58rem;font-weight:600;text-transform:uppercase;padding:.4rem .4rem;text-align:center;white-space:nowrap;position:sticky;top:0;z-index:2}
.items-table td{padding: 5px 5px !important;border-bottom:1px solid #e7e5e4;text-align:center;vertical-align:middle}
.items-table tbody tr:nth-child(even){background:#fafaf9}
.items-table tbody tr:hover{background:#fef2f2} 
.items-table input:focus,.items-table select:focus{border-color:#7f1d1d;outline:none;box-shadow:0 0 0 2px rgba(127,29,29,.08)}
.items-table input[readonly]{background:#f5f5f4;color:#57534e}
.btn-add-row{background:#7f1d1d;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-del-row{background:#dc2626;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.form-footer{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-top:1px solid #e7e5e4;background:#fafaf9;flex-shrink:0}
.form-footer .btn-cancel{margin-right:auto}
.btn-draft{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.btn-draft:hover{background:#6b1a1a}
.btn-submit{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#16a34a;color:#fff;cursor:pointer}
.btn-submit:hover{background:#15803d}
.btn-back{height:34px;padding:0 1rem;font-size:.72rem;font-weight:600;border:1px solid #d6d3d1;border-radius:.5rem;background:#fff;color:#57534e;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem}
#alertBox{display:none;padding:.5rem .75rem;border-radius:.5rem;font-size:.7rem;margin-bottom:.5rem}
#alertBox.error{display:block;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
#alertBox.success{display:block;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
</style>
@endpush

@section('content')
<div class="entry-grid">
    {{-- Left: File Viewer Panel --}}
    <div class="file-panel">
        <div class="file-tabs">
            <button class="file-tab active" data-url="{{ $scanData->File_Location }}" data-name="{{ $scanData->File }}">Main Scan</button>
            @foreach($supportFiles as $sf)
                <button class="file-tab" data-url="{{ $sf->File_Location }}" data-name="{{ $sf->File }}">{{ $sf->doc_type_name ?: 'Support' }}</button>
            @endforeach
        </div>
        <div class="file-viewer" id="fileViewer">
            @if(strtolower($scanData->File_Ext) === 'pdf')
                <iframe src="{{ $scanData->File_Location }}"></iframe>
            @else
                <img src="{{ $scanData->File_Location }}" alt="{{ $scanData->File }}">
            @endif
        </div>
    </div>

    {{-- Right: Form Panel --}}
    <div style="display:flex;flex-direction:column;overflow:hidden">
        <div class="form-panel" id="formPanel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                <div>
                    <h2 style="font-size:.85rem;font-weight:700;color:#292524">Invoice Entry — Scan #{{ $scanData->Scan_Id }}</h2>
                    <p style="font-size:.6rem;color:#78716c">{{ $scanData->company_name }} • {{ $scanData->doc_type_label }}</p>
                </div>
                <a href="{{ route('workflow.punching.index') }}" class="btn-back">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back
                </a>
            </div>

            <div id="alertBox"></div>

            <form id="entryForm" novalidate>
                @csrf
                <input type="hidden" name="Scan_Id" value="{{ $scanData->Scan_Id }}">

                @include('panel.workflow.punching.forms.' . $formPartial, [
                    'scanData' => $scanData,
                    'punchDetail' => $punchDetail,
                    'tempData' => $tempData,
                ])
            </form>
        </div>

        {{-- Footer Buttons --}}
        <div class="form-footer">
            <a href="{{ route('workflow.punching.index') }}" class="btn-cancel btn-back">Cancel</a>
            <button type="button" id="btnDraft" class="btn-draft">Save Draft</button>
            <button type="button" id="btnSubmit" class="btn-submit">Final Submit</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const SCAN_ID = {{ $scanData->Scan_Id }};
    const R = {
        items: `/workflow/punching/entry/${SCAN_ID}/items`,
        save: `/workflow/punching/entry/${SCAN_ID}/save`,
        selItems: '{{ route("workflow.punching.entry.select.items") }}',
        selItemsCreate: '{{ route("workflow.punching.entry.select.items.create") }}',
        selUnits: '{{ route("workflow.punching.entry.select.units") }}',
        selBuyers: '{{ route("workflow.punching.entry.select.buyers") }}',
        selVendors: '{{ route("workflow.punching.entry.select.vendors") }}',
        selDepts: '{{ route("workflow.punching.entry.select.departments") }}',
        selCategories: '{{ route("workflow.punching.entry.select.categories") }}',
        selLedgers: '{{ route("workflow.punching.entry.select.ledgers") }}',
        selLocations: '{{ route("workflow.punching.entry.select.locations") }}',
        selFiles: '{{ route("workflow.punching.entry.select.files") }}'
    };

    // ========== File Tabs ==========
    $('.file-tab').on('click', function(){
        $('.file-tab').removeClass('active');
        $(this).addClass('active');
        const url = $(this).data('url');
        const $v = $('#fileViewer');
        $v.find('iframe, img').remove();
        if(url.toLowerCase().endsWith('.pdf')){
            $v.append(`<iframe src="${url}"></iframe>`);
        } else {
            $v.append(`<img src="${url}">`);
        }
    });

    // ========== Select2 Inits ==========
    function s2(sel, url, placeholder){
        $(sel).select2({
            placeholder: placeholder || 'Select…',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            }
        });
    }
    s2('#selBuyer', R.selBuyers, 'Search Buyer');
    s2('#selVendor', R.selVendors, 'Search Vendor');
    s2('#selDept', R.selDepts, 'Select Department');
    s2('#selCategory', R.selCategories, 'Select Category');
    s2('#selLedger', R.selLedgers, 'Select Ledger');
    s2('#selLocation', R.selLocations, 'Select Location');
    s2('#selFile', R.selFiles, 'Select File');

    // Address auto-fill on buyer/vendor select
    $('#selBuyer').on('select2:select', function(e){
        $('input[name="Buyer_Address"]').val(e.params.data.address || '');
    });
    $('#selVendor').on('select2:select', function(e){
        $('input[name="Vendor_Address"]').val(e.params.data.address || '');
    });

    // ========== Units as Select2 (server-side) ==========
    function initUnitSelect2(sel, selectedVal, selectedText) {
        $(sel).select2({
            placeholder: 'Unit',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selUnits,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            }
        });
        if (selectedVal && selectedText) {
            $(sel).append(new Option(selectedText, selectedVal, true, true)).trigger('change');
        }
    }
    initUnitSelect2('.unit-sel');

    // ========== Item Select2 with Tags ==========
    function initParticular(sel){
        $(sel).select2({
            placeholder: 'Search Item',
            allowClear: true,
            tags: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selItems,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            },
            createTag: function(params){
                var term = $.trim(params.term);
                if(term === '') return null;
                return { id: term, text: term, newTag: true };
            }
        }).on('select2:select', function(e){
            var data = e.params.data;
            if(data.newTag){
                var $s = $(this);
                $.ajax({
                    url: R.selItemsCreate,
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': CSRF},
                    data: { item_name: data.text }
                }).done(function(r){
                    if(r.success){
                        $s.find('option[value="'+data.id+'"]').val(r.item.id);
                    }
                });
            }
        });
    }
    initParticular('.particular-sel');

    // ========== Numeric validation — only allow numbers and decimal ==========
    $(document).on('keypress', '.calc-trigger', function(e) {
        var charCode = e.which || e.keyCode;
        if (charCode === 46 && $(this).val().indexOf('.') !== -1) return false; // only one dot
        if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) return false;
        return true;
    });
    $(document).on('paste', '.calc-trigger', function(e) {
        var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (!/^\d*\.?\d*$/.test(paste)) e.preventDefault();
    });

    // ========== Add / Remove Rows ==========
    let rowCount = 1;

    function addRow(){
        rowCount++;
        const tr = `<tr>
            <td>${rowCount}</td>
            <td><select name="Particular[]" class="particular-sel" style="width:100%"><option value="">Select</option></select></td>
            <td><input type="text" name="HSN[]"></td>
            <td><input type="text" name="Qty[]" class="calc-trigger" inputmode="decimal"></td>
            <td><select name="Unit[]" class="unit-sel"></select></td>
            <td><input type="text" name="MRP[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Discount[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Price[]" readonly></td>
            <td><input type="text" name="Amount[]" class="amt-field" readonly></td>
            <td><input type="text" name="GST[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="SGST[]" readonly></td>
            <td><input type="text" name="IGST[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Cess[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="TAmount[]" class="total-field" readonly></td>
            <td><button type="button" class="btn-del-row">−</button></td>
        </tr>`;
        $('#itemsBody').append(tr);
        initParticular('#itemsBody tr:last .particular-sel');
        initUnitSelect2('#itemsBody tr:last .unit-sel');
    }

    $(document).on('click', '.btn-add-row', addRow);
    $(document).on('click', '.btn-del-row', function(){
        $(this).closest('tr').remove();
        reindex();
        calcTotals();
    });

    function reindex(){
        $('#itemsBody tr').each(function(i){
            $(this).find('td:first').text(i + 1);
        });
        rowCount = $('#itemsBody tr').length;
    }

    // ========== Row Calculations ==========
    $(document).on('input', '.calc-trigger', function(){
        const $tr = $(this).closest('tr');
        calcRow($tr);
        calcTotals();
    });

    function calcRow($tr){
        const qty = parseFloat($tr.find('input[name="Qty[]"]').val()) || 0;
        const mrp = parseFloat($tr.find('input[name="MRP[]"]').val()) || 0;
        const discount = parseFloat($tr.find('input[name="Discount[]"]').val()) || 0;
        const gst = parseFloat($tr.find('input[name="GST[]"]').val()) || 0;
        const igst = parseFloat($tr.find('input[name="IGST[]"]').val()) || 0;
        const cess = parseFloat($tr.find('input[name="Cess[]"]').val()) || 0;

        // Price = MRP - Discount
        const price = mrp - discount;
        $tr.find('input[name="Price[]"]').val(price.toFixed(2));

        // Amount = Qty * Price
        const amount = qty * price;
        $tr.find('input[name="Amount[]"]').val(amount.toFixed(2));

        // SGST = GST / 2
        const sgst = gst / 2;
        $tr.find('input[name="SGST[]"]').val(sgst.toFixed(2));

        // Total = Amount + GST amount + IGST amount + Cess amount
        const gstAmt = (amount * gst) / 100;
        const igstAmt = (amount * igst) / 100;
        const cessAmt = (amount * cess) / 100;
        const total = amount + gstAmt + igstAmt + cessAmt;
        $tr.find('input[name="TAmount[]"]').val(total.toFixed(2));
    }

    function calcTotals(){
        let subTotal = 0;
        $('#itemsBody tr').each(function(){
            subTotal += parseFloat($(this).find('input[name="TAmount[]"]').val()) || 0;
        });
        $('#subTotal').val(subTotal.toFixed(2));

        const tcs = parseFloat($('#tcsField').val()) || 0;
        const total = subTotal + (subTotal * tcs / 100);
        $('#totalField').val(total.toFixed(2));
        $('#grandTotal').val(total.toFixed(2));
    }

    $('#tcsField').on('input', calcTotals);

    // ========== Load Existing Items via AJAX ==========
    $.ajax({
        url: R.items,
        method: 'GET',
        headers: {'X-CSRF-TOKEN': CSRF},
        dataType: 'json'
    }).done(function(data){
        if(data && data.length > 0){
            $('#itemsBody').empty();
            rowCount = 0;
            data.forEach(function(item){
                rowCount++;
                const tr = `<tr>
                    <td>${rowCount}</td>
                    <td><select name="Particular[]" class="particular-sel" style="width:100%"><option value="${item.Item_ID || ''}" selected>${item.Item_Name || ''}</option></select></td>
                    <td><input type="text" name="HSN[]" value="${item.HSN || ''}"></td>
                    <td><input type="text" name="Qty[]" class="calc-trigger" value="${item.Qty || ''}"></td>
                    <td><select name="Unit[]" class="unit-sel"></select></td>
                    <td><input type="text" name="MRP[]" class="calc-trigger" value="${item.MRP || ''}"></td>
                    <td><input type="text" name="Discount[]" class="calc-trigger" value="${item.Discount || ''}"></td>
                    <td><input type="text" name="Price[]" readonly value="${item.Price || ''}"></td>
                    <td><input type="text" name="Amount[]" class="amt-field" readonly value="${item.Amount || ''}"></td>
                    <td><input type="text" name="GST[]" class="calc-trigger" value="${item.GST || ''}"></td>
                    <td><input type="text" name="SGST[]" readonly value="${item.SGST || ''}"></td>
                    <td><input type="text" name="IGST[]" class="calc-trigger" value="${item.IGST || ''}"></td>
                    <td><input type="text" name="Cess[]" class="calc-trigger" value="${item.Cess || ''}"></td>
                    <td><input type="text" name="TAmount[]" class="total-field" readonly value="${item.TAmount || ''}"></td>
                    <td><button type="button" class="btn-del-row">−</button></td>
                </tr>`;
                $('#itemsBody').append(tr);
                initParticular('#itemsBody tr:last .particular-sel');
                initUnitSelect2('#itemsBody tr:last .unit-sel', item.Unit || '', item.Unit || '');
            });
            // Add a blank row with + button
            addRow();
            // Replace last row's delete button with add button
            $('#itemsBody tr:last .btn-del-row').removeClass('btn-del-row').addClass('btn-add-row').text('+');
        }
    });

    // ========== Save (Draft / Final Submit) ==========
    function showAlert(msg, type){
        $('#alertBox').removeClass('error success').addClass(type).text(msg).show();
        setTimeout(function(){ $('#alertBox').fadeOut(); }, 5000);
    }

    function save(action){
        const formData = $('#entryForm').serializeArray();
        formData.push({ name: 'action', value: action });

        // Validate required fields
        const billNo = $('input[name="Bill_No"]').val();
        const billDate = $('input[name="Bill_Date"]').val();
        const buyer = $('#selBuyer').val();
        const vendor = $('#selVendor').val();
        const location = $('#selLocation').val();

        if(action === 'final_submit'){
            if(!billNo || !billDate || !buyer || !vendor || !location){
                showAlert('Please fill all required fields (Invoice No, Date, Buyer, Vendor, Location)', 'error');
                return;
            }
        }

        $.ajax({
            url: R.save,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': CSRF},
            data: $.param(formData),
            dataType: 'json'
        }).done(function(res){
            if(res.success){
                showAlert(res.message || 'Saved successfully!', 'success');
                if(action === 'final_submit'){
                    setTimeout(function(){
                        window.location.href = '{{ route("workflow.punching.index") }}';
                    }, 1000);
                }
            } else {
                showAlert(res.message || 'Save failed.', 'error');
            }
        }).fail(function(xhr){
            const msg = xhr.responseJSON?.message || 'An error occurred.';
            showAlert(msg, 'error');
        });
    }

    $('#btnDraft').on('click', function(){ save('draft'); });
    $('#btnSubmit').on('click', function(){ save('final_submit'); });
});
</script>
@endpush
