<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>PraticaVoice</title>
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
    .theme-dark .back-button {
        background-color: #1f2937;
        color: #f9fafb;
        border: 1px solid #4b5563;
    }
    .theme-dark .back-button:hover {
        background-color: #374151;
        color: #f9fafb;
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
    .theme-dark a { color: #93c5fd; }
    .theme-dark a:hover { color: #bfdbfe; text-decoration: underline; }
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
                <a href="/voice.html" class="text-blue-600 underline">🎤 Ricerca vocale</a>
            </div>
            <button id="themeToggle"
                    class="px-3 py-1 rounded border border-gray-200 text-sm font-medium bg-gray-50 hover:bg-gray-100 transition">
                🌙 Modalità scura
            </button>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto">
        @yield('content')
    </div>

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
