{{-- Company form partial — used inside the offcanvas/modal --}}
{{-- Expects Alpine: form, errors, tab --}}

{{-- Tabs --}}
<div class="flex border-b border-stone-100 overflow-x-auto shrink-0">
    @foreach([
        ['identity', 'Identity',  'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9'],
        ['address',  'Address',   'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        ['tax',      'Tax & GST', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
        ['bank',     'Bank',      'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ['finance',  'Financial', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ] as [$key, $label, $icon])
    <button type="button" @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'"
            class="flex items-center gap-1.5 px-4 py-3 text-xs transition-colors whitespace-nowrap shrink-0">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
        </svg>
        {{ $label }}
    </button>
    @endforeach
</div>

<div class="flex-1 overflow-y-auto p-5 space-y-4">

    {{-- ── Identity ── --}}
    <div x-show="tab === 'identity'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="form-label">Company Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.name" placeholder="e.g. Acme Technologies Pvt Ltd"
                       class="form-input" :class="errors.name ? 'border-red-400' : ''">
                <p x-show="errors.name" x-text="errors.name" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Legal Name</label>
                <input type="text" x-model="form.legal_name" placeholder="Registered legal name"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Display Name</label>
                <input type="text" x-model="form.display_name" placeholder="Short display name"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Company Code</label>
                <input type="text" x-model="form.code" placeholder="e.g. ACME" maxlength="20"
                       class="form-input" :class="errors.code ? 'border-red-400' : ''">
                <p x-show="errors.code" x-text="errors.code" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Company Type <span class="text-red-500">*</span></label>
                <select x-model="form.type" class="form-input">
                    <option value="private_limited">Private Limited</option>
                    <option value="public_limited">Public Limited</option>
                    <option value="llp">LLP</option>
                    <option value="partnership">Partnership</option>
                    <option value="proprietorship">Proprietorship</option>
                    <option value="trust">Trust</option>
                    <option value="ngo">NGO</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="form-label">Industry</label>
                <input type="text" x-model="form.industry" placeholder="e.g. Information Technology"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Email</label>
                <input type="email" x-model="form.email" placeholder="info@company.com"
                       class="form-input" :class="errors.email ? 'border-red-400' : ''">
                <p x-show="errors.email" x-text="errors.email" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input type="text" x-model="form.phone" placeholder="+91 22 1234 5678"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Mobile</label>
                <input type="text" x-model="form.mobile" placeholder="+91 98765 43210"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Website</label>
                <input type="url" x-model="form.website" placeholder="https://www.company.com"
                       class="form-input" :class="errors.website ? 'border-red-400' : ''">
                <p x-show="errors.website" x-text="errors.website" class="form-error"></p>
            </div>
            <div class="col-span-2">
                <label class="form-label">Description</label>
                <textarea x-model="form.description" rows="2" placeholder="Brief description of the company"
                          class="form-input resize-none"></textarea>
            </div>
        </div>
    </div>

    {{-- ── Address ── --}}
    <div x-show="tab === 'address'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="form-label">Address Line 1</label>
                <input type="text" x-model="form.address_line1" placeholder="Building, Street"
                       class="form-input">
            </div>
            <div class="col-span-2">
                <label class="form-label">Address Line 2</label>
                <input type="text" x-model="form.address_line2" placeholder="Area, Landmark"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">City</label>
                <input type="text" x-model="form.city" placeholder="Mumbai"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">State</label>
                <select x-model="form.state" class="form-input">
                    <option value="">— Select State —</option>
                    @foreach(['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'] as $state)
                    <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Pincode</label>
                <input type="text" x-model="form.pincode" placeholder="400001" maxlength="10"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Country</label>
                <input type="text" x-model="form.country" placeholder="India"
                       class="form-input">
            </div>
        </div>
    </div>

    {{-- ── Tax & GST ── --}}
    <div x-show="tab === 'tax'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="form-label">GSTIN</label>
                <input type="text" x-model="form.gstin" placeholder="22AAAAA0000A1Z5" maxlength="20"
                       class="form-input font-mono" :class="errors.gstin ? 'border-red-400' : ''">
                <p x-show="errors.gstin" x-text="errors.gstin" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">GST Registration Type</label>
                <select x-model="form.gst_registration_type" class="form-input">
                    <option value="regular">Regular</option>
                    <option value="composition">Composition</option>
                    <option value="unregistered">Unregistered</option>
                    <option value="sez">SEZ</option>
                    <option value="overseas">Overseas</option>
                </select>
            </div>
            <div>
                <label class="form-label">GST Registration Date</label>
                <input type="date" x-model="form.gst_registration_date" class="form-input">
            </div>
            <div>
                <label class="form-label">PAN</label>
                <input type="text" x-model="form.pan" placeholder="AAAAA0000A" maxlength="20"
                       class="form-input font-mono">
            </div>
            <div>
                <label class="form-label">TAN</label>
                <input type="text" x-model="form.tan" placeholder="AAAA00000A" maxlength="20"
                       class="form-input font-mono">
            </div>
            <div>
                <label class="form-label">CIN</label>
                <input type="text" x-model="form.cin" placeholder="U12345MH2020PTC123456" maxlength="30"
                       class="form-input font-mono">
            </div>
            <div class="col-span-2">
                <label class="form-label">MSME Number</label>
                <input type="text" x-model="form.msme_number" placeholder="UDYAM-XX-00-0000000" maxlength="30"
                       class="form-input font-mono">
            </div>
        </div>
    </div>

    {{-- ── Bank ── --}}
    <div x-show="tab === 'bank'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="form-label">Bank Name</label>
                <input type="text" x-model="form.bank_name" placeholder="State Bank of India"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Branch</label>
                <input type="text" x-model="form.bank_branch" placeholder="Fort, Mumbai"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Account Type</label>
                <select x-model="form.bank_account_type" class="form-input">
                    <option value="">— Select —</option>
                    <option value="Current">Current</option>
                    <option value="Savings">Savings</option>
                    <option value="Overdraft">Overdraft</option>
                    <option value="Cash Credit">Cash Credit</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="form-label">Account Number</label>
                <input type="text" x-model="form.bank_account_number" placeholder="00000000000000"
                       class="form-input font-mono tracking-widest">
            </div>
            <div>
                <label class="form-label">IFSC Code</label>
                <input type="text" x-model="form.bank_ifsc" placeholder="SBIN0000001" maxlength="15"
                       class="form-input font-mono">
            </div>
            <div>
                <label class="form-label">SWIFT Code</label>
                <input type="text" x-model="form.bank_swift" placeholder="SBININBB" maxlength="15"
                       class="form-input font-mono">
            </div>
        </div>
    </div>

    {{-- ── Financial ── --}}
    <div x-show="tab === 'finance'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="form-label">Financial Year Start</label>
                <select x-model="form.fy_start_month" class="form-input">
                    @foreach(['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $num => $month)
                    <option value="{{ $num }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Timezone</label>
                <select x-model="form.timezone" class="form-input">
                    @foreach(['Asia/Kolkata','UTC','America/New_York','America/Los_Angeles','Europe/London','Europe/Paris','Asia/Dubai','Asia/Singapore','Asia/Tokyo','Australia/Sydney'] as $tz)
                    <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Currency Code</label>
                <select x-model="form.currency_code" class="form-input">
                    @foreach(['INR'=>'INR — Indian Rupee','USD'=>'USD — US Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — British Pound','AED'=>'AED — UAE Dirham','SGD'=>'SGD — Singapore Dollar','JPY'=>'JPY — Japanese Yen','AUD'=>'AUD — Australian Dollar'] as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Currency Symbol</label>
                <input type="text" x-model="form.currency_symbol" placeholder="₹" maxlength="5"
                       class="form-input font-mono">
            </div>
            <div>
                <label class="form-label">Date Format</label>
                <select x-model="form.date_format" class="form-input">
                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                </select>
            </div>
        </div>
    </div>

</div>
