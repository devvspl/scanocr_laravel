<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Digital Signature — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>canvas { touch-action: none; }</style>
</head>
<body class="bg-stone-100 min-h-screen flex items-center justify-center p-4">

<div x-data="signatureUpload()" x-init="init()" class="w-full max-w-lg">

    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-stone-800">Upload Your Digital Signature</h1>
        <p class="text-sm text-stone-500 mt-1">Hi <strong>{{ $user->name }}</strong>, draw or upload your signature below</p>
    </div>

    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-bold text-stone-600 uppercase tracking-wide">Your Signature</label>
            <div class="flex items-center gap-2">
                <button @click="clearCanvas()" class="text-xs text-stone-400 hover:text-red-600 font-medium transition-colors">Clear</button>
                <span class="text-stone-200">|</span>
                <label class="text-xs text-stone-400 hover:text-blue-600 font-medium cursor-pointer transition-colors">
                    Upload Image
                    <input type="file" accept="image/*" @change="uploadImage($event)" class="hidden">
                </label>
            </div>
        </div>

        <div class="border-2 border-dashed border-stone-200 rounded-xl overflow-hidden bg-stone-50 relative" style="height:200px;">
            <canvas x-ref="canvas" width="460" height="200"
                @mousedown="startDraw($event)" @mousemove="draw($event)" @mouseup="endDraw()"
                @mouseleave="endDraw()"
                @touchstart.prevent="startDrawTouch($event)" @touchmove.prevent="drawTouch($event)" @touchend="endDraw()"
                class="w-full h-full cursor-crosshair"></canvas>
            <div x-show="!hasSignature && !drawing" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <p class="text-sm text-stone-300">Draw your signature here</p>
            </div>
        </div>

        @if($existingUrl)
        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-medium text-green-700">You already have a saved signature</span>
            </div>
            <button @click="loadExisting()" class="text-xs font-semibold text-green-700 hover:text-green-800">Load it</button>
        </div>
        @endif
    </div>

    <button @click="saveSignature()" :disabled="submitting || !hasSignature"
        class="w-full h-12 flex items-center justify-center gap-2 text-sm font-semibold text-white rounded-xl transition-colors disabled:opacity-50"
        :style="hasSignature ? 'background:#7f1d1d' : 'background:#a8a29e'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="submitting ? 'Saving…' : 'Save Signature'"></span>
    </button>

    <div x-show="resultMsg" x-cloak class="mt-4 p-4 rounded-xl text-center text-sm font-semibold"
         :class="resultType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
         x-text="resultMsg"></div>
</div>

<script>
function signatureUpload() {
    return {
        drawing: false, hasSignature: false, ctx: null, lastX: 0, lastY: 0,
        submitting: false, resultMsg: '', resultType: '',

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

        getPos(e) { const r = this.$refs.canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; },
        startDraw(e) { this.drawing = true; const p = this.getPos(e); this.lastX = p.x; this.lastY = p.y; },
        draw(e) { if (!this.drawing) return; const p = this.getPos(e); this.ctx.beginPath(); this.ctx.moveTo(this.lastX, this.lastY); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); this.lastX = p.x; this.lastY = p.y; this.hasSignature = true; },
        endDraw() { this.drawing = false; },
        startDrawTouch(e) { const t = e.touches[0]; this.drawing = true; const r = this.$refs.canvas.getBoundingClientRect(); this.lastX = t.clientX - r.left; this.lastY = t.clientY - r.top; },
        drawTouch(e) { if (!this.drawing) return; const t = e.touches[0]; const r = this.$refs.canvas.getBoundingClientRect(); const x = t.clientX - r.left, y = t.clientY - r.top; this.ctx.beginPath(); this.ctx.moveTo(this.lastX, this.lastY); this.ctx.lineTo(x, y); this.ctx.stroke(); this.lastX = x; this.lastY = y; this.hasSignature = true; },

        clearCanvas() { this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height); this.hasSignature = false; },

        uploadImage(e) {
            const file = e.target.files[0]; if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = new Image();
                img.onload = () => { this.clearCanvas(); const c = this.$refs.canvas; const s = Math.min(c.width / img.width, c.height / img.height) * 0.9; const w = img.width * s, h = img.height * s; this.ctx.drawImage(img, (c.width - w) / 2, (c.height - h) / 2, w, h); this.hasSignature = true; };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        },

        loadExisting() {
            const img = new Image(); img.crossOrigin = 'anonymous';
            img.onload = () => { this.clearCanvas(); const c = this.$refs.canvas; const s = Math.min(c.width / img.width, c.height / img.height) * 0.85; const w = img.width * s, h = img.height * s; this.ctx.drawImage(img, (c.width - w) / 2, (c.height - h) / 2, w, h); this.hasSignature = true; };
            img.src = '{{ $existingUrl ?? '' }}';
        },

        async saveSignature() {
            if (!this.hasSignature) return;
            this.submitting = true; this.resultMsg = '';
            try {
                const res = await fetch('/signature/upload/{{ $token }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ signature: this.$refs.canvas.toDataURL('image/png') }),
                });
                const json = await res.json();
                this.resultMsg = json.message; this.resultType = json.success ? 'success' : 'error';
            } catch (err) { this.resultMsg = 'Network error.'; this.resultType = 'error'; }
            finally { this.submitting = false; }
        },
    };
}
</script>
</body>
</html>
