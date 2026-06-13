<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Digital Signature — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        canvas { touch-action: none; }
    </style>
</head>
<body class="bg-stone-100 min-h-screen flex items-center justify-center p-4">

<div x-data="signaturePage()" class="w-full max-w-lg">

    {{-- Header --}}
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-stone-800">Digital Signature</h1>
        <p class="text-sm text-stone-500 mt-1">Sign to approve or reject this document</p>
    </div>

    {{-- Document Info Card --}}
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 mb-4">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-stone-800">{{ ucfirst(str_replace('_', ' ', $log->document_type)) }}</p>
                <p class="text-xs text-stone-400">{{ $log->level_name ?? 'Level ' . $log->level }} Approval</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-xs">
            <div><span class="text-stone-400">Approver:</span><p class="font-semibold text-stone-800">{{ $user->name }}</p></div>
            <div><span class="text-stone-400">Email:</span><p class="font-medium text-stone-600">{{ $user->email }}</p></div>
        </div>
        @if($requireSignature)
        <div class="mt-3 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-[11px] font-semibold text-amber-700">⚠️ Digital signature is required for this approval level</p>
        </div>
        @endif
    </div>

    {{-- Use Saved Signature --}}
    @if($userSignatureUrl)
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-4 mb-4" x-show="!usingSaved || hasSignature">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-semibold text-stone-700">You have a saved signature</span>
            </div>
            <button @click="useSavedSignature()" class="text-xs font-semibold text-red-700 hover:text-red-800 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                Use Saved Signature
            </button>
        </div>
    </div>
    @endif

    {{-- Signature Pad --}}
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-bold text-stone-600 uppercase tracking-wide">Draw Your Signature</label>
            <div class="flex items-center gap-2">
                <button @click="clearCanvas()" class="text-xs text-stone-400 hover:text-red-600 font-medium transition-colors">Clear</button>
                <span class="text-stone-200">|</span>
                <label class="text-xs text-stone-400 hover:text-blue-600 font-medium cursor-pointer transition-colors">
                    Upload Image
                    <input type="file" accept="image/*" @change="uploadImage($event)" class="hidden">
                </label>
            </div>
        </div>

        <div class="border-2 border-dashed border-stone-200 rounded-xl overflow-hidden bg-stone-50 relative" style="height:180px;">
            <canvas x-ref="canvas" width="460" height="180"
                @mousedown="startDraw($event)" @mousemove="draw($event)" @mouseup="endDraw()"
                @mouseleave="endDraw()"
                @touchstart.prevent="startDrawTouch($event)" @touchmove.prevent="drawTouch($event)" @touchend="endDraw()"
                class="w-full h-full cursor-crosshair"></canvas>
            <div x-show="!hasSignature && !drawing" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <p class="text-xs text-stone-300">Draw your signature here</p>
            </div>
        </div>

        {{-- Remarks --}}
        <div class="mt-4">
            <label class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Remarks (optional)</label>
            <textarea x-model="remarks" rows="2" placeholder="Any comments…"
                class="w-full mt-1 px-3 py-2 text-sm border border-stone-200 rounded-xl bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-3">
        <button @click="submitAction('reject')" :disabled="submitting"
            class="flex-1 h-11 flex items-center justify-center gap-2 text-sm font-semibold text-red-700 border-2 border-red-200 rounded-xl hover:bg-red-50 transition-colors disabled:opacity-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Reject
        </button>
        <button @click="submitAction('approve')" :disabled="submitting || !hasSignature"
            class="flex-1 h-11 flex items-center justify-center gap-2 text-sm font-semibold text-white rounded-xl transition-colors disabled:opacity-50"
            :style="hasSignature ? 'background:#166534' : 'background:#a8a29e'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span x-text="submitting ? 'Submitting…' : 'Sign & Approve'"></span>
        </button>
    </div>

    {{-- Save for future --}}
    <div class="mt-3 flex items-center gap-2" x-show="hasSignature">
        <input type="checkbox" x-model="saveForFuture" id="save-sig" class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700">
        <label for="save-sig" class="text-xs text-stone-600 cursor-pointer">Save this signature for future approvals</label>
    </div>

    {{-- Success/Error --}}
    <div x-show="resultMsg" x-cloak class="mt-4 p-4 rounded-xl text-center text-sm font-semibold"
         :class="resultType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
         x-text="resultMsg"></div>
</div>

<script>
function signaturePage() {
    return {
        drawing: false, hasSignature: false, ctx: null, lastX: 0, lastY: 0,
        remarks: '', submitting: false, resultMsg: '', resultType: '',
        usingSaved: false, saveForFuture: true,

        init() {
            this.$nextTick(() => {
                const canvas = this.$refs.canvas;
                this.ctx = canvas.getContext('2d');
                this.ctx.strokeStyle = '#1c1917';
                this.ctx.lineWidth = 2.5;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
            });
        },

        useSavedSignature() {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.clearCanvas();
                const canvas = this.$refs.canvas;
                const scale = Math.min(canvas.width / img.width, canvas.height / img.height) * 0.85;
                const w = img.width * scale, h = img.height * scale;
                const x = (canvas.width - w) / 2, y = (canvas.height - h) / 2;
                this.ctx.drawImage(img, x, y, w, h);
                this.hasSignature = true;
                this.usingSaved = true;
            };
            img.src = '{{ $userSignatureUrl ?? '' }}';
        },

        getPos(e) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        },

        startDraw(e) { this.drawing = true; const p = this.getPos(e); this.lastX = p.x; this.lastY = p.y; },
        draw(e) {
            if (!this.drawing) return;
            const p = this.getPos(e);
            this.ctx.beginPath(); this.ctx.moveTo(this.lastX, this.lastY); this.ctx.lineTo(p.x, p.y); this.ctx.stroke();
            this.lastX = p.x; this.lastY = p.y; this.hasSignature = true;
        },
        endDraw() { this.drawing = false; },

        startDrawTouch(e) { const t = e.touches[0]; this.drawing = true; const rect = this.$refs.canvas.getBoundingClientRect(); this.lastX = t.clientX - rect.left; this.lastY = t.clientY - rect.top; },
        drawTouch(e) {
            if (!this.drawing) return;
            const t = e.touches[0]; const rect = this.$refs.canvas.getBoundingClientRect();
            const x = t.clientX - rect.left, y = t.clientY - rect.top;
            this.ctx.beginPath(); this.ctx.moveTo(this.lastX, this.lastY); this.ctx.lineTo(x, y); this.ctx.stroke();
            this.lastX = x; this.lastY = y; this.hasSignature = true;
        },

        clearCanvas() {
            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            this.hasSignature = false;
        },

        uploadImage(e) {
            const file = e.target.files[0]; if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = new Image();
                img.onload = () => {
                    this.clearCanvas();
                    const canvas = this.$refs.canvas;
                    const scale = Math.min(canvas.width / img.width, canvas.height / img.height) * 0.9;
                    const w = img.width * scale, h = img.height * scale;
                    const x = (canvas.width - w) / 2, y = (canvas.height - h) / 2;
                    this.ctx.drawImage(img, x, y, w, h);
                    this.hasSignature = true;
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        },

        async submitAction(action) {
            if (action === 'approve' && !this.hasSignature) { this.resultMsg = 'Please draw or upload your signature first.'; this.resultType = 'error'; return; }
            this.submitting = true; this.resultMsg = '';
            const signatureData = action === 'approve' ? this.$refs.canvas.toDataURL('image/png') : '';
            try {
                const res = await fetch(`/approval/sign/{{ $token }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ signature: signatureData, remarks: this.remarks, action, save_for_future: this.saveForFuture }),
                });
                const json = await res.json();
                this.resultMsg = json.message; this.resultType = json.success ? 'success' : 'error';
            } catch (err) { this.resultMsg = 'Network error. Please try again.'; this.resultType = 'error'; }
            finally { this.submitting = false; }
        },
    };
}
</script>
</body>
</html>
