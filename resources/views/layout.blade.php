<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>{{ \App\Support\Tenant::name() }} — PraticaVoice</title>
    <script>
    // Applica il tema salvato il prima possibile per evitare flash
    (function () {
        const stored = localStorage.getItem('pv-theme');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = stored || (prefersDark ? 'dark' : 'light');
        const root = document.documentElement;
        root.classList.remove('theme-light', 'theme-dark');
        root.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
    })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.min.js"></script>
    <style>
    :root {
        --pv-bg: #f3f4f6;
        --pv-surface: #ffffff;
        --pv-text: #111827;
        --pv-muted: #4b5563;
        --pv-border: #e5e7eb;
        --pv-card: #ffffff;
        --pv-nav: #ffffff;
        --pv-back-bg: #f3f4f6;
        --pv-back-text: #374151;
    }
    .theme-dark {
        --pv-bg: #111827;
        --pv-surface: #0f172a;
        --pv-text: #e5e7eb;
        --pv-muted: #9ca3af;
        --pv-border: #374151;
        --pv-card: #1f2937;
        --pv-nav: #111827;
        --pv-back-bg: #1f2937;
        --pv-back-text: #e5e7eb;
    }

    body {
        background-color: var(--pv-bg);
        color: var(--pv-text);
    }

    nav, .nav-bar {
        background-color: var(--pv-nav);
        color: var(--pv-text);
    }

    .card, .bg-white {
        background-color: var(--pv-card) !important;
        color: var(--pv-text) !important;
    }
    .bg-gray-50 { background-color: #f9fafb !important; }
    .theme-dark .bg-gray-50 { background-color: #111827 !important; color: var(--pv-text) !important; }
    .theme-dark .bg-gray-100 { background-color: #1f2937 !important; color: var(--pv-text) !important; }
    .theme-dark .text-gray-800 { color: #e5e7eb !important; }
    .theme-dark .text-gray-700 { color: #d1d5db !important; }
    .theme-dark .text-gray-600 { color: #9ca3af !important; }
    .theme-dark .text-gray-500 { color: #9ca3af !important; }
    .theme-dark .border-gray-200 { border-color: #374151 !important; }
    .theme-dark .shadow { box-shadow: 0 4px 12px rgba(0,0,0,0.4) !important; }

    .back-button {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }
    .back-button:hover {
        background-color: #e5e7eb;
    }
    .theme-dark .back-button,
    .dark .back-button {
        background-color: #1f2937;
        color: #f9fafb;
        border: 1px solid #4b5563;
    }
    .theme-dark .back-button:hover,
    .dark .back-button:hover {
        background-color: #374151;
        color: #f9fafb;
    }
    /* Header coerente col logo in dark */
    .theme-dark .nav-bar,
    .dark .nav-bar {
        background-color: #020617 !important;
        color: #e5e7eb !important;
    }
    .theme-dark header,
    .dark header {
        background-color: #020617;
        color: #e5e7eb;
    }

    /* Stacco visivo header / body in dark mode */
    .theme-dark body,
    .dark body {
        background-color: #0f172a;
    }
    .theme-dark .page,
    .theme-dark .main-content,
    .dark .page,
    .dark .main-content {
        background-color: #0f172a;
    }

    /* Select in dark mode più leggibile */
    .theme-dark select,
    .dark select {
        background-color: #1e293b;
        color: #ffffff;
        border: 1px solid #475569;
    }

    /* Box e testi documenti in dark */
    .theme-dark .elem,
    .theme-dark .document-box,
    .dark .elem,
    .dark .document-box {
        background-color: #020617 !important;
        border: 1px solid #334155 !important;
        color: #e5e7eb !important;
    }
    .theme-dark .elem h3,
    .theme-dark .document-box h3,
    .dark .elem h3,
    .dark .document-box h3 {
        color: #e5e7eb !important;
    }
    .theme-dark .elem p,
    .theme-dark .document-box p,
    .dark .elem p,
    .dark .document-box p {
        color: #cbd5e5 !important;
    }
    .theme-dark .card,
    .theme-dark .panel,
    .theme-dark .box,
    .dark .card,
    .dark .panel,
    .dark .box {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #e5e7eb !important;
    }

    /* Form: input, select, textarea in dark mode */
    .theme-dark input,
    .theme-dark textarea,
    .theme-dark select,
    .dark input,
    .dark textarea,
    .dark select {
        background-color: #020617;
        color: #f8fafc;
        border: 1px solid #334155;
    }
    .theme-dark input::placeholder,
    .theme-dark textarea::placeholder,
    .dark input::placeholder,
    .dark textarea::placeholder {
        color: #94a3b8;
    }
    .theme-dark input:focus,
    .theme-dark textarea:focus,
    .theme-dark select:focus,
    .dark input:focus,
    .dark textarea:focus,
    .dark select:focus {
        outline: none;
        border-color: #60a5fa;
        box-shadow: 0 0 0 1px #60a5fa;
    }
    .theme-dark label,
    .theme-dark .form-label,
    .dark label,
    .dark .form-label {
        color: #e5e7eb;
    }
    .theme-dark .text-muted,
    .dark .text-muted {
        color: #94a3b8;
    }

    /* Tabelle in dark mode */
    .theme-dark table,
    .dark table {
        background-color: #020617;
        color: #e5e7eb;
    }
    .theme-dark table thead,
    .dark table thead {
        background-color: #0f172a;
        color: #f8fafc;
    }
    .theme-dark table tbody tr,
    .dark table tbody tr {
        background-color: #020617;
    }
    .theme-dark table tbody tr:nth-child(even),
    .theme-dark table tbody tr:nth-child(odd),
    .dark table tbody tr:nth-child(even),
    .dark table tbody tr:nth-child(odd) {
        background-color: #020617;
    }
    .theme-dark table tbody tr:hover,
    .dark table tbody tr:hover {
        background-color: #1e293b;
    }
    .theme-dark table a,
    .dark table a {
        color: #93c5fd;
    }
    .theme-dark table a:hover,
    .dark table a:hover {
        color: #bfdbfe;
        text-decoration: underline;
    }

    /* Bottoni generici in dark mode */
    .theme-dark .btn,
    .dark .btn {
        color: #f8fafc;
    }

    /* Paginazione in dark mode */
    /* Paginazione dark: wrapper e controlli compatti */
    .theme-dark .pagination-wrapper,
    .dark .pagination-wrapper {
        background-color: #020617;
        border-top: 1px solid #334155;
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .theme-dark .pagination-info,
    .theme-dark .text-pagination,
    .dark .pagination-info,
    .dark .text-pagination {
        color: #94a3b8;
        font-size: 0.875rem;
    }
    .theme-dark .pagination,
    .dark .pagination {
        display: inline-flex;
        gap: 0;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid #334155;
        background-color: #020617;
    }
    .theme-dark .pagination a,
    .theme-dark .pagination span,
    .dark .pagination a,
    .dark .pagination span {
        background-color: #020617;
        color: #e5e7eb;
        padding: 0.4rem 0.75rem;
        border-right: 1px solid #334155;
        font-size: 0.875rem;
    }
    .theme-dark .pagination a:last-child,
    .theme-dark .pagination span:last-child,
    .dark .pagination a:last-child,
    .dark .pagination span:last-child {
        border-right: none;
    }
    .theme-dark .pagination a:hover,
    .dark .pagination a:hover {
        background-color: #1e293b;
        color: #ffffff;
    }
    .theme-dark .pagination .active span,
    .dark .pagination .active span {
        background-color: #2563eb;
        color: #ffffff;
        font-weight: 600;
        border-color: #2563eb;
    }
    .theme-dark .pagination .disabled span,
    .dark .pagination .disabled span {
        background-color: #020617;
        color: #475569;
        border-color: #334155;
        cursor: not-allowed;
    }

    /* Stato caricamento PDF */
    .pdf-loading {
        border: 2px dashed #cbd5e1;
        background-color: #f9fafb;
    }
    .pdf-loaded {
        border: 1px solid var(--pv-border);
        background-color: var(--pv-card);
    }
    .pdf-loading .file-loaded { display: none !important; }
    .pdf-loaded .file-loaded { display: inline-flex !important; }
    .pdf-loaded .file-spinner { display: none !important; }

    /* Link leggibili in dark mode */
    .theme-dark a, .dark a { color: #93c5fd; }
    .theme-dark a:hover, .dark a:hover { color: #bfdbfe; text-decoration: underline; }
    .logo-light { display: block; }
    .logo-dark { display: none; }
    .theme-dark .logo-light { display: none; }
    .theme-dark .logo-dark { display: block; }

    .modal-bg {
        background: rgba(0,0,0,0.6);
    }
    .thumb-wrapper {
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .thumb-wrapper:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .thumb-selected {
        outline: 3px solid #2563eb;
        border-radius: 4px;
    }
    </style>

</head>
<body>

    <nav class="bg-white shadow p-4 mb-6 nav-bar">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') ?? '/' }}" class="flex items-center">
                    <img src="{{ asset('img/logoOrizzontale.png') }}"
                        alt="PraticaVoice"
                        class="logo-light h-16 w-auto">
                    <img src="{{ asset('img/logoOrizzontaleDark.png') }}"
                        alt="PraticaVoice"
                        class="logo-dark h-16 w-auto">
                </a>
                <span class="text-sm font-semibold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900 dark:text-blue-100 dark:border-blue-800">
                    {{ \App\Support\Tenant::name() }}
                </span>
                <a href="/voice.html" class="text-blue-600 underline">🎤 Ricerca vocale</a>
            </div>
            <button id="themeToggle"
                    class="px-3 py-1 rounded border border-gray-200 text-sm font-medium bg-gray-50 hover:bg-gray-100 transition">
                🌙 Modalità scura
            </button>
        </div>
    </nav>

<div class="max-w-7xl mx-auto main-content">
    @yield('content')
</div>

<footer class="max-w-7xl mx-auto mt-10 mb-6 px-4 text-sm text-gray-500 dark:text-gray-400">
    <div class="flex flex-wrap items-center gap-3">
        <span class="font-semibold text-gray-700 dark:text-gray-200">PraticaVoice</span>
        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700">
            {{ $appVersion }} · {{ config('praticavoice.mode') }}
        </span>
        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900 dark:text-blue-100 dark:border-blue-800">
            {{ \App\Support\Tenant::name() }}
        </span>
        <a href="{{ route('info-sistema') }}" class="text-blue-600 hover:underline">Info sistema</a>
    </div>
</footer>
    <script>
    (function () {
        const btn = document.getElementById('themeToggle');
        const root = document.documentElement;

        const setTheme = (theme) => {
            root.classList.remove('theme-light', 'theme-dark');
            root.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            localStorage.setItem('pv-theme', theme);
            if (btn) {
                btn.textContent = theme === 'dark' ? '☀️ Modalità chiara' : '🌙 Modalità scura';
            }
        };

        const initTheme = () => {
            const stored = localStorage.getItem('pv-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored || (prefersDark ? 'dark' : 'light');
            setTheme(theme);
        };

        if (btn) {
            btn.addEventListener('click', () => {
                const isDark = root.classList.contains('theme-dark');
                setTheme(isDark ? 'light' : 'dark');
            });
        }

        initTheme();
    })();
    </script>
</body>
</html>
