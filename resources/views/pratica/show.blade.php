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

    @if(session('swiss_link'))
        <div class="alert alert-info mb-3">
            📤 Link SwissTransfer: <a href="{{ session('swiss_link') }}" target="_blank" class="text-blue-700 underline">{{ session('swiss_link') }}</a>
        </div>
    @endif

    {{-- TORNA ALLA DASHBOARD --}}
    <button onclick="returnToDashboard()"
            class="mb-4 px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded">
        ⬅ Torna alla dashboard
    </button>

    <script>
        function returnToDashboard() {
            const qs = localStorage.getItem('dashboardReturn') || '';
            window.location = '/dashboard' + qs;
        }
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
        <a href="{{ route('accesso-atti.create', $pratica->id) }}"
           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded shadow">
            📘 Nuovo fascicolo Accesso agli Atti
        </a>
    </div>

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
                            name="swiss_transfer"
                            value="0">
                        📦 Scarica ZIP selezionati (<span id="zipCount">0</span>)
                    </button>

                    <button type="submit" id="swissBtn"
                            class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 disabled:opacity-50"
                            disabled
                            name="swiss_transfer"
                            value="1">
                        📤 Invia via SwissTransfer
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

        {{-- JS ZIP + ANTEPRIMA PDF --}}
        <script>
            const zipForm  = document.querySelector('form[action$="/zip"]');
            const zipBtn   = document.getElementById('zipBtn');
            const swissBtn = document.getElementById('swissBtn');
            const zipCount = document.getElementById('zipCount');
            const zipLoadingText = document.getElementById('zipLoadingText');

            function refreshZipButton() {
                const checkboxes = [...document.querySelectorAll('.pdf-check')];
                const checked    = checkboxes.filter(cb => cb.checked).length;

                const disabled = checked === 0;
                zipBtn.disabled = disabled;
                swissBtn.disabled = disabled;
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
                const submitter = event.submitter;
                if (submitter && submitter.id === 'swissBtn') {
                    zipLoadingText.textContent = 'Preparazione ZIP e upload su SwissTransfer...';
                } else {
                    zipLoadingText.textContent = 'Preparazione ZIP...';
                }
                document.getElementById('zipLoading')?.classList.remove('d-none');
            });

            refreshZipButton();

            // 🔥 Cambio PDF nell'iframe quando clicchi "Anteprima"
            document.querySelectorAll('.pdf-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const viewer = document.getElementById('pdfViewer');
                    if (viewer) {
                        viewer.src = this.href;
                    }
                });
            });
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
            src="{{ $autoPdf ?? '' }}"
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
