{{-- CDN replacement for @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

{{-- Tailwind CSS v4 Play CDN (browser JIT) --}}
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
            },
        }
    }
}
</script>

{{-- Axios via CDN --}}
<script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
<script>
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
</script>

{{-- Form element base styles (replaces @tailwindcss/forms plugin) ──── */
<style>
/* ── Form resets (equivalent to @tailwindcss/forms) ──── */
/* Note: Detailed styling is in custom.css, these are just base resets */

:where([type='checkbox']),
:where([type='radio']) {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding: 0;
    print-color-adjust: exact;
    display: inline-block;
    vertical-align: middle;
    background-origin: border-box;
    user-select: none;
    flex-shrink: 0;
    height: 1rem;
    width: 1rem;
    color: #7f1d1d;
    background-color: #fff;
    border-color: #d6d3d1;
    border-width: 1px;
    border-style: solid;
}

:where([type='checkbox']) {
    border-radius: 0.25rem;
}

:where([type='radio']) {
    border-radius: 100%;
}

:where([type='checkbox']:checked),
:where([type='radio']:checked) {
    border-color: transparent;
    background-color: #450a0a;
    background-size: 100% 100%;
    background-position: center;
    background-repeat: no-repeat;
}

:where([type='checkbox']:checked) {
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
}

:where([type='radio']:checked) {
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
}

:where([type='checkbox']:focus),
:where([type='radio']:focus) {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(127, 29, 29, 0.08);
}
</style>

{{-- Custom app styles (from resources/css/app.css) --}}
<style>
/* ── Core tokens ─────────────────────────────────────────── */
:root {
    --brand:        #7f1d1d;
    --brand-dark:   #450a0a;
    --brand-light:  #fef2f2;
    --accent:       #b91c1c;
    --sidebar-w:    260px;
    --topbar-h:     64px;
}

/* ── Sidebar nav items ───────────────────────────────────── */
.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #a8a29e;
    transition: all 150ms;
    cursor: pointer;
    user-select: none;
}
.nav-item:hover {
    background: rgba(255,255,255,.05);
    color: #f5f5f4;
}
.nav-item.active {
    background: rgba(127,29,29,.4);
    color: #fca5a5;
    box-shadow: inset 3px 0 0 #ef4444;
}
.nav-item .icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

/* ── Stat card ───────────────────────────────────────────── */
.stat-card {
    position: relative;
    overflow: hidden;
    background-color: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 1rem;
    padding: 1.25rem;
    transition: box-shadow 200ms;
}
.stat-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
}
.stat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.6) 0%, transparent 60%);
    pointer-events: none;
}

/* ── Mobile overlay ──────────────────────────────────────── */
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 20;
}
@media (min-width: 1024px) {
    .sidebar-overlay { display: none; }
}

/* ── Scrollbar polish ────────────────────────────────────── */
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }

/* ── Collapsed nav-item icon centering ───────────────────── */
.nav-item.\!px-0 {
    padding-left: 0 !important;
    padding-right: 0 !important;
    justify-content: center;
    gap: 0;
}
</style>
