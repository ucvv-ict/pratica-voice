@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded-lg">

    {{-- TITOLO --}}
    <h1 class="text-2xl font-bold mb-2">
        📁 Pratica {{ $pratica->numero_pratica }}
    </h1>

    <h4 class="text-gray-600 mb-4">
        {{ $pratica->oggetto }}
    </h4>

    {{-- MESSAGGI FLASH --}}
    @if(session('error'))
        <div class="alert alert-danger mb-3">
            ❌ {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success mb-3">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('r2_link'))
        <div class="alert alert-info mb-3">
            📤 Link R2: <a href="{{ session('r2_link') }}" target="_blank" class="text-blue-700 underline">{{ session('r2_link') }}</a>
            @if(session('r2_expires_at'))
                <span class="text-sm text-gray-700 ml-2">(scade: {{ \Carbon\Carbon::parse(session('r2_expires_at'))->format('d/m/Y H:i') }})</span>
            @endif
        </div>
    @endif

    @php
        $ultimaGenerazione = $fascicoli->first();
        $fascicoloReady = $ultimaGenerazione && $ultimaGenerazione->stato === 'completed';
    @endphp

    {{-- STATO CARICAMENTO DOCUMENTI --}}
    <div id="pdfLoading"
         class="mb-4 p-3 rounded bg-yellow-50 border border-yellow-200 text-yellow-800">
        ⏳ Caricamento documenti della pratica…
    </div>

    @if($ultimaGenerazione)
        <div class="mb-4 p-4 border border-gray-200 rounded bg-gray-50" id="fascicoloStatus"
             data-status-url="{{ route('pratica.fascicolo.status', [$pratica->id, $ultimaGenerazione->id]) }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <p class="font-semibold">Stato fascicolo (versione {{ $ultimaGenerazione->versione }})</p>
                    <p>Stato: <span id="fascicoloStato">{{ $ultimaGenerazione->stato }}</span></p>
                    <p>Avanzamento: <span id="fascicoloProgress">{{ $ultimaGenerazione->progress }}</span>%</p>
                    <p id="fascicoloReady"
                       class="text-green-700 font-semibold {{ $fascicoloReady ? '' : 'hidden' }}">
                        ✅ Fascicolo pronto
                    </p>
                    @if($ultimaGenerazione->errore)
                        <p class="text-red-600" id="fascicoloError">{{ $ultimaGenerazione->errore }}</p>
                    @else
                        <p class="text-red-600 hidden" id="fascicoloError"></p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $fascicoloReady ? route('pratica.fascicolo.download', [$pratica->id, $ultimaGenerazione->id]) : '#' }}"
                       data-download-url="{{ route('pratica.fascicolo.download', [$pratica->id, $ultimaGenerazione->id]) }}"
                       id="fascicoloDownload"
                       aria-disabled="{{ $fascicoloReady ? 'false' : 'true' }}"
                       @if(!$fascicoloReady) tabindex="-1" @endif
                       class="px-3 py-1 bg-green-600 text-white rounded shadow hover:bg-green-700 {{ $fascicoloReady ? '' : 'pointer-events-none opacity-50' }}">
                        ⬇ Scarica ZIP
                    </a>
                    <button type="button"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
                            onclick="window.location.reload()">
                        Aggiorna
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- TORNA ALLA DASHBOARD --}}
    <div id="dashboardBackWrapper" class="mb-4">
        <x-back-button href="{{ url('/dashboard') }}">
            Torna alla dashboard
        </x-back-button>
    </div>

    <script>
        (function () {
            const wrapper = document.getElementById('dashboardBackWrapper');
            if (!wrapper) return;
            const link = wrapper.querySelector('a');
            if (!link) return;

            link.addEventListener('click', function (event) {
                event.preventDefault();
                const qs = localStorage.getItem('dashboardReturn') || '';
                window.location = '/dashboard' + qs;
            });
        })();
    </script>

    {{-- ACCESSI AGLI ATTI ESISTENTI --}}
    @if($accessi->count() > 0)
    <details open class="mb-6 border border-gray-300 rounded p-4 bg-white shadow-sm">
        <summary class="cursor-pointer font-bold text-lg flex items-center gap-2">
            📄 Accessi agli Atti già generati ({{ $accessi->count() }})
        </summary>

        <div class="mt-4">
            <div class="overflow-x-auto rounded border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs border-b">
                        <tr>
                            <th class="px-4 py-2 text-left">Versione</th>
                            <th class="px-4 py-2 text-left">Data creazione</th>
                            <th class="px-4 py-2 text-left">Note</th>
                            <th class="px-4 py-2 text-right">Azione</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($accessi as $a)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 font-semibold text-gray-800">
                                    {{ $a->versione }}
                                </td>

                                <td class="px-4 py-2 text-gray-700">
                                    {{ $a->created_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-4 py-2 text-gray-600">
                                    {{ $a->note ?: '—' }}
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('accesso-atti.show', $a->id) }}"
                                    class="px-3 py-1 bg-blue-600 text-white rounded shadow hover:bg-blue-700">
                                        Apri fascicolo
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </details>
    @endif

    {{-- PULSANTE NUOVO FASCICOLO --}}
    <div class="mb-6">
        <a id="newAccessoBtn"
           href="{{ route('accesso-atti.create', $pratica->id) }}"
           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded shadow">
            📘 Nuovo fascicolo Accesso agli Atti
        </a>
    </div>
    <script>
        // Disabilita click multipli sul pulsante di apertura nuovo fascicolo
        (function () {
            const btn = document.getElementById('newAccessoBtn');
            if (!btn) return;

            btn.addEventListener('click', (event) => {
                if (btn.dataset.clicked === 'true') {
                    event.preventDefault();
                    return;
                }

                btn.dataset.clicked = 'true';
                btn.textContent = '⏳ Apertura…';
                btn.classList.add('opacity-60', 'pointer-events-none', 'cursor-not-allowed');
                if ('disabled' in btn) btn.disabled = true; // copre eventuale <button> nel futuro
            });
        })();
    </script>

    @php
        // stessi calcoli che avevi prima
        $search        = request('pdf');
        $requestedFile = request('file');
        $autoPdf       = null;

        if ($requestedFile) {
            foreach ($pdfFiles as $file) {
                if ($file['name'] === $requestedFile) {
                    $autoPdf = $file['url'];
                    break;
                }
            }
        }

        if (!$autoPdf && $search) {
            foreach ($pdfFiles as $file) {
                if (stripos($file['content'] ?? '', $search) !== false) {
                    $autoPdf = $file['url'];
                    break;
                }
            }
        }
    @endphp

    {{-- RICHIEDENTI --}}
    <details open class="mb-4">
        <summary class="font-semibold">🧑‍💼 Richiedenti</summary>
        <div class="mt-2">
            <p><b>1)</b> {{ $pratica->rich_cognome1 }} {{ $pratica->rich_nome1 }}</p>

            @if($pratica->rich_cognome2 || $pratica->rich_nome2)
                <p><b>2)</b> {{ $pratica->rich_cognome2 }} {{ $pratica->rich_nome2 }}</p>
            @endif

            @if($pratica->rich_cognome3 || $pratica->rich_nome3)
                <p><b>3)</b> {{ $pratica->rich_cognome3 }} {{ $pratica->rich_nome3 }}</p>
            @endif
        </div>
    </details>

    {{-- DATI PRINCIPALI --}}
    <details open class="mb-4">
        <summary class="font-semibold">📌 Dati principali</summary>

        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p><b>Tipo pratica:</b> {{ $pratica->sigla_tipo_pratica }}</p>
                <p><b>Anno presentazione:</b> {{ $pratica->anno_presentazione }}</p>
                <p><b>Riferimento libero:</b> {{ $pratica->riferimento_libero }}</p>
            </div>

            <div>
                <p><b>Data protocollo:</b> {{ $pratica->data_protocollo }}</p>
                <p><b>Numero protocollo:</b> {{ $pratica->numero_protocollo }}</p>
                <p><b>Pratica ID interno:</b> {{ $pratica->pratica_id }}</p>
            </div>
        </div>
    </details>

    {{-- RILASCIO --}}
    <details class="mb-4">
        <summary class="font-semibold">📑 Rilascio</summary>
        <div class="mt-2">
            <p><b>Data rilascio:</b> {{ $pratica->data_rilascio }}</p>
            <p><b>Numero rilascio:</b> {{ $pratica->numero_rilascio }}</p>
        </div>
    </details>

    {{-- LOCALIZZAZIONE --}}
    <details class="mb-4">
        <summary class="font-semibold">📬 Localizzazione</summary>
        <div class="mt-2">
            <p><b>Via:</b> {{ $pratica->area_circolazione }}</p>
            <p><b>Civico:</b> {{ $pratica->civico_esponente }}</p>
        </div>
    </details>

    {{-- DATI CATASTALI --}}
    <details class="mb-6">
        <summary class="font-semibold">📍 Dati catastali</summary>

        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p><b>Foglio:</b> {{ $pratica->foglio }}</p>
                <p><b>Particella / Sub:</b> {{ $pratica->particella_sub }}</p>
            </div>

            <div>
                <p><b>Sezione:</b> {{ $pratica->sezione }}</p>
                <p><b>Tipo catasto:</b> {{ $pratica->tipo_catasto }}</p>
            </div>

            <div>
                <p><b>Codice catasto:</b> {{ $pratica->codice_catasto }}</p>
                <p><b>Nota:</b> {{ $pratica->nota }}</p>
            </div>
        </div>
    </details>

    <hr class="my-6">

    {{-- DOCUMENTI PDF --}}
    <h2 class="text-xl font-semibold mb-3">📄 Documenti associati</h2>

    @if (count($pdfFiles) === 0)
        <p class="text-gray-500">
            Nessun documento trovato nella cartella <b>{{ $pratica->cartella }}</b>.
        </p>
    @else

        <div id="pdfContainer" style="max-height: 70vh; overflow-y: auto; display: none;">

        <div class="w-full bg-gray-200 rounded h-3 mb-3">
            <div id="pdfProgressBar" class="bg-blue-600 h-3 rounded" style="width: 0%;"></div>
        </div>
        <p class="text-sm text-gray-600 mb-3">Caricamento PDF: <span id="pdfProgressText">0%</span></p>

        <form method="POST" action="/pratica/{{ $pratica->id }}/zip">
            @csrf

            <div class="flex justify-between mb-3">

                <div class="flex gap-2">
                    <button type="button" id="selectAll" class="btn btn-sm btn-secondary">
                        Seleziona tutti
                    </button>
                    <button type="button" id="deselectAll" class="btn btn-sm btn-secondary">
                        Deseleziona tutti
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" id="zipBtn"
                            class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700 disabled:opacity-50"
                            disabled
                            name="r2_upload"
                            value="0">
                        📦 Avvia generazione (queue) (<span id="zipCount">0</span>)
                    </button>

                    <span id="zipLoading"
                        class="hidden text-gray-500 text-sm flex items-center gap-1">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 
                                    3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="zipLoadingText">Preparazione ZIP...</span>
                    </span>
                </div>
            </div>

            <ul class="space-y-2 mb-4">

                @foreach ($pdfFiles as $pdf)
                    <li class="flex justify-between items-center p-3 border rounded bg-gray-50 hover:bg-gray-100">

                        {{-- Nome + checkbox --}}
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                class="pdf-check h-4 w-4"
                                name="files[]"
                                value="{{ $pdf['name'] }}">

                            <span class="font-medium text-gray-800">{{ $pdf['name'] }}</span>
                        </div>

                        {{-- Azioni --}}
                        <div class="flex gap-2">

                            {{-- ANTEPRIMA --}}
                            <a href="{{ $pdf['url'] }}"
                            class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 pdf-link">
                                👁️ Anteprima
                            </a>

                            {{-- APRI --}}
                            <a href="{{ $pdf['url'] }}" target="_blank"
                            class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">
                                🔗 Apri
                            </a>
                        </div>

                    </li>
                @endforeach

            </ul>
        </form>
        </div>

        {{-- JS ZIP + ANTEPRIMA PDF --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('pdfLoading')?.classList.add('hidden');
                const pdfContainer = document.getElementById('pdfContainer');
                if (pdfContainer) {
                    pdfContainer.style.display = 'block';
                }
            });

            const zipForm  = document.querySelector('form[action$="/zip"]');
            const zipBtn   = document.getElementById('zipBtn');
            const zipCount = document.getElementById('zipCount');
            const zipLoadingText = document.getElementById('zipLoadingText');

            function refreshZipButton() {
                const checkboxes = [...document.querySelectorAll('.pdf-check')];
                const checked    = checkboxes.filter(cb => cb.checked).length;

                const disabled = checked === 0;
                zipBtn.disabled = disabled;
                zipCount.textContent = checked;
            }

            document.getElementById('selectAll').onclick = () => {
                document.querySelectorAll('.pdf-check').forEach(cb => cb.checked = true);
                refreshZipButton();
            };

            document.getElementById('deselectAll').onclick = () => {
                document.querySelectorAll('.pdf-check').forEach(cb => cb.checked = false);
                refreshZipButton();
            };

            document.querySelectorAll('.pdf-check').forEach(cb => {
                cb.addEventListener('change', refreshZipButton);
            });

            zipForm.addEventListener('submit', (event) => {
                zipLoadingText.textContent = 'Fascicolo messo in coda...';
                document.getElementById('zipLoading')?.classList.remove('hidden');
            });

            refreshZipButton();

            // 🔥 Cambio PDF nell'iframe quando clicchi "Anteprima"
            document.querySelectorAll('.pdf-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const viewer = document.getElementById('pdfViewer');
                    if (viewer) {
                        viewer.dataset.src = this.href;
                        viewer.src = this.href;
                    }
                });
            });

            // Lazy load PDF iframe + progress
            (function setupPdfLazyLoading() {
                const pdfElements = [...document.querySelectorAll('[data-pdf-lazy]')];
                if (!pdfElements.length) return;

                let loaded = 0;
                const total = pdfElements.length;
                const progressBar = document.getElementById('pdfProgressBar');
                const progressText = document.getElementById('pdfProgressText');

                const updateProgress = () => {
                    const pct = Math.min(100, Math.round((loaded / total) * 100));
                    if (progressBar) progressBar.style.width = pct + '%';
                    if (progressText) progressText.textContent = pct + '%';
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) return;
                        const el = entry.target;
                        observer.unobserve(el);
                        if (!el.getAttribute('src')) {
                            const src = el.dataset.src || '';
                            el.setAttribute('src', src);
                            el.addEventListener('load', () => {
                                loaded++;
                                updateProgress();
                            }, { once: true });
                        }
                    });
                }, {
                    root: document.getElementById('pdfContainer'),
                    threshold: 0.1
                });

                pdfElements.forEach(el => observer.observe(el));
                updateProgress();
            })();

            // Polling semplice per lo stato dell'ultimo fascicolo generato
            const statusBox = document.getElementById('fascicoloStatus');
            if (statusBox) {
                const statusUrl = statusBox.dataset.statusUrl;
                const statoEl = document.getElementById('fascicoloStato');
                const progressEl = document.getElementById('fascicoloProgress');
                const errorEl = document.getElementById('fascicoloError');
                const downloadEl = document.getElementById('fascicoloDownload');
                const readyEl = document.getElementById('fascicoloReady');

                const toggleDownload = (isReady, url) => {
                    if (!downloadEl) return;
                    if (isReady && url) {
                        downloadEl.href = url;
                        downloadEl.classList.remove('pointer-events-none', 'opacity-50', 'hidden');
                        downloadEl.setAttribute('aria-disabled', 'false');
                        downloadEl.removeAttribute('tabindex');
                    } else {
                        downloadEl.href = '#';
                        downloadEl.classList.add('pointer-events-none', 'opacity-50');
                        downloadEl.setAttribute('aria-disabled', 'true');
                        downloadEl.setAttribute('tabindex', '-1');
                    }
                };

                const refreshStatus = async () => {
                    try {
                        const resp = await fetch(statusUrl);
                        const data = await resp.json();
                        statoEl.textContent = data.stato;
                        progressEl.textContent = data.progress ?? 0;
                        if (data.errore) {
                            errorEl.textContent = data.errore;
                            errorEl.classList.remove('hidden');
                        } else {
                            errorEl.classList.add('hidden');
                            errorEl.textContent = '';
                        }

                        if (data.download) {
                            toggleDownload(true, data.download);
                            readyEl?.classList.remove('hidden');
                        } else {
                            toggleDownload(false, null);
                            readyEl?.classList.add('hidden');
                        }

                        if (data.stato !== 'completed' && data.stato !== 'error') {
                            setTimeout(refreshStatus, 5000);
                        }
                    } catch (err) {
                        console.error('Impossibile aggiornare lo stato del fascicolo', err);
                    }
                };

                if (statoEl && progressEl) {
                    const initialReady = (statoEl.textContent || '').trim() === 'completed';
                    toggleDownload(initialReady, initialReady ? downloadEl?.dataset.downloadUrl : null);
                    if (!initialReady) {
                        readyEl?.classList.add('hidden');
                    }
                    refreshStatus();
                }
            }
        </script>

        {{-- BLOCCO ANTEPRIMA --}}
        <h3 class="text-lg font-semibold mb-2">👁️ Anteprima documento</h3>

        <p class="text-gray-500">
            @if($search)
                Stai visualizzando la pratica filtrata per <b>"{{ $search }}"</b>.
                @if($autoPdf)
                    È stato aperto automaticamente il primo PDF che contiene il testo.
                @else
                    Nessun PDF di questa pratica contiene esattamente quel testo.
                @endif
            @else
                Clicca su "Anteprima" accanto a un PDF per visualizzarlo qui sotto.
            @endif
        </p>

        <iframe
            id="pdfViewer"
            data-src="{{ $autoPdf ?? '' }}"
            data-pdf-lazy="1"
            src=""
            width="100%"
            height="800"
            class="border rounded">
        </iframe>

    @endif

    <hr class="my-6">

    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded"
            onclick="window.location='/voice.html'">
        🎤 Cerca con la voce
    </button>

</div>

@endsection
