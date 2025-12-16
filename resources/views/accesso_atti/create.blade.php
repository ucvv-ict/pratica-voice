@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded-lg">

    <h1 class="text-2xl font-bold mb-6">📘 Nuovo Fascicolo – Pratica {{ $praticaId }}</h1>

    <div class="mb-4">
        <x-back-button href="{{ route('pratica.show', $praticaId) }}">
            Torna alla pratica
        </x-back-button>
    </div>

    <form id="formAccesso" method="POST" action="{{ route('accesso-atti.store', $praticaId) }}">
        @csrf

        <div id="pdfLoading"
             class="mb-4 p-3 rounded bg-yellow-50 border border-yellow-200 text-yellow-800">
            ⏳ Caricamento documenti della pratica…
        </div>

        {{-- DESCRIZIONE --}}
        <label class="block text-sm font-semibold mb-1">Descrizione</label>
        <input type="text" name="descrizione"
               class="w-full border-gray-300 rounded-md shadow-sm mb-6">

        {{-- CARICAMENTO GLOBALE --}}
        <div id="globalLoading"
             class="mt-3 mb-6 p-3 rounded bg-gray-50 border flex items-center gap-3">
            <svg id="globalSpinner" class="animate-spin h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
            </svg>
            <div class="flex-1">
                <span class="text-sm text-gray-700">
                    Caricamento documenti: <strong><span id="pdfProgressText">0%</span></strong>
                </span>
                <div class="progress mt-2" style="height: 0.45rem;">
                    <div id="pdfProgressBar"
                         class="progress-bar"
                         role="progressbar"
                         style="width: 0%;"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        {{-- NOTE --}}
        <label class="block text-sm font-semibold mb-1">Note (facoltative)</label>
        <textarea name="note"
                rows="2"
                class="w-full border-gray-300 rounded-md shadow-sm mb-6"
                placeholder="Annotazioni interne, informazioni utili, ecc."></textarea>

        <h2 class="text-lg font-bold mb-2">Documenti della pratica</h2>
        <p class="text-gray-600 mb-4">
            Seleziona i fogli che vuoi includere nel fascicolo.
        </p>

        <div id="pdfContainer" style="max-height: 70vh; overflow-y: auto; display: none;">
            {{-- Vista compatta durante il caricamento --}}
            <div id="pdfLoadingList" class="space-y-3">
            @foreach($files as $f)
                @php
                    $pdfUrl = $f->public_url;
                @endphp
                <div class="pdf-loading flex items-center justify-between bg-gray-50 p-3 rounded border"
                     data-file-row="{{ $f->id }}">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-800 truncate" title="{{ $f->file }}">
                            📄 {{ $f->file }}
                        </h3>
                        <p class="text-sm text-gray-500 flex items-center">
                            Fogli: {{ $f->num_pagine }}
                            <span class="file-spinner animate-spin text-gray-400 ml-2"
                                  data-file-spinner="{{ $f->id }}">⏳</span>
                            <span class="file-loaded text-green-700 text-sm ml-2"
                                  data-file-badge="{{ $f->id }}">✔ Caricato</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3" style="min-width: 180px;">
                        <span class="text-sm text-gray-600 whitespace-nowrap">Caricamento:</span>
                        <div class="progress flex-1" style="height: 0.45rem;">
                            <div class="progress-bar text-xs"
                                 role="progressbar"
                                 style="width: 0%"
                                 aria-valuenow="0"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 data-progress-bar="{{ $f->id }}">
                                0%
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>

            {{-- Vista estesa mostrata solo a caricamento completato --}}
            <div id="pdfExpandedList" class="hidden">
                <div id="lista" class="space-y-6">

                @foreach($files as $f)
                    @php
                        $pdfUrl = $f->public_url;
                    @endphp

                    <div class="elem pdf-loading bg-gray-50 p-4 rounded border shadow-sm"
                         draggable="true"
                         data-file-row="{{ $f->id }}"
                         data-file-id="{{ $f->id }}"
                         data-pdf-url="{{ $pdfUrl }}"
                         id="file-{{ $f->id }}">

                        {{-- HEADER FILE --}}
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $f->file }}</h3>
                                <p class="text-sm text-gray-500">
                                    Fogli totali: {{ $f->num_pagine }}
                                </p>
                            </div>
                            <span class="file-loaded text-green-700 text-sm"
                                  data-file-badge="{{ $f->id }}">✔ Caricato</span>
                        </div>

                        {{-- THUMBNAILS --}}
                        <div id="thumbs-{{ $f->id }}"
                             data-thumbnails="{{ $f->id }}"
                             class="flex flex-wrap gap-3 text-center text-sm text-gray-600 hidden">
                        </div>

                        <iframe class="hidden pdf-lazy"
                                data-src="{{ $pdfUrl }}"
                                data-file-id="{{ $f->id }}"
                                data-total-pages="{{ $f->num_pagine }}"
                                loading="lazy"
                                tabindex="-1"
                                title="Caricamento PDF {{ $f->file }}"></iframe>
                    </div>
                @endforeach

                </div>
            </div>

            </div>
        </div>

        <input type="hidden" name="elementi" id="elementiJson">

        <button id="saveBtn"
                type="submit"
                disabled
                class="mt-6 px-5 py-3 bg-green-600 text-white font-semibold rounded shadow opacity-50 cursor-not-allowed">
            ⏳ Attendere caricamento…
        </button>

    </form>
</div>


{{-- MODALE PREVIEW PDF --}}
<div id="modalPdf"
     class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
    <div class="bg-white w-11/12 h-[90vh] rounded shadow-xl overflow-hidden">
        <div class="p-3 flex justify-between bg-gray-100 border-b">
            <h2 class="font-semibold">Anteprima documento</h2>
            <button onclick="closePreview()" class="text-red-600 font-bold">✖</button>
        </div>

        <iframe id="pdfFrame" class="w-full h-full"></iframe>
    </div>
</div>


<script>
// Limiti di concorrenza configurabili
const MAX_PARALLEL_PDF = 2;

// ===============================
// PREVIEW PDF (modale)
// ===============================
function openPreview(url) {
    document.getElementById('pdfFrame').src = url;
    document.getElementById('modalPdf').classList.remove('hidden');
}
function closePreview() {
    document.getElementById('modalPdf').classList.add('hidden');
    document.getElementById('pdfFrame').src = '';
}


// ===============================
// GENERA THUMBNAILS + PROGRESS PDF
// ===============================
async function generateThumbnails(pdfUrl, containerId, fileId) {

    return new Promise(async (resolve, reject) => {

        const MAX_PAGES_IN_FLIGHT = 4;
        const container = document.getElementById(containerId);
        const progressBar = document.querySelector(`[data-progress-bar="${fileId}"]`);

        container.innerHTML = '';
        container.classList.add('hidden');

        let pdf;
        try {
            pdf = await pdfjsLib.getDocument(pdfUrl).promise;
        } catch (e) {
            if (progressBar) {
                progressBar.classList.add('bg-danger');
                progressBar.textContent = 'Errore';
            }
            return resolve(); // non blocca gli altri
        }

        const numPages = pdf.numPages;
        let loaded = 0;
        const loadedNodes = new Array(numPages);

        const updateProgress = () => {
            const pct = Math.round((loaded / numPages) * 100);
            if (progressBar) {
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
                progressBar.setAttribute('aria-valuenow', pct);
            }
        };

        updateProgress();

        const renderPage = async (pageNum) => {
            try {
                const page = await pdf.getPage(pageNum);
                const viewport = page.getViewport({ scale: 0.22 });

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: ctx, viewport }).promise;

                const img = new Image();
                img.src = canvas.toDataURL('image/png');
                img.alt = `Pagina ${pageNum}`;
                img.className = 'block mx-auto';

                await new Promise((res, rej) => {
                    img.onload = res;
                    img.onerror = rej;
                });

                const wrapper = document.createElement('label');
                wrapper.className = 'inline-block cursor-pointer';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = pageNum;
                checkbox.name = `pages[${fileId}][]`;
                checkbox.className = 'block mx-auto mt-1';

                checkbox.addEventListener('change', () => {
                    wrapper.classList.toggle('thumb-selected', checkbox.checked);
                });

                wrapper.appendChild(img);
                wrapper.appendChild(checkbox);
                loadedNodes[pageNum - 1] = wrapper;
            } catch (e) {
                // Continuiamo comunque a contare la pagina per non bloccare il completamento
            } finally {
                loaded++;
                updateProgress();

                // 👉 SOLO QUI mostriamo TUTTO INSIEME
                if (loaded === numPages) {
                    container.innerHTML = '';
                    loadedNodes.forEach(n => {
                        if (n) container.appendChild(n);
                    });
                    container.classList.remove('hidden');

                    if (progressBar) {
                        progressBar.classList.add('bg-success');
                        progressBar.textContent = '✔ Caricato';
                    }
                    resolve();
                }
            }
        };

        const pageQueue = Array.from({ length: numPages }, (_, i) => i + 1);
        const runNextPage = async () => {
            const next = pageQueue.shift();
            if (next === undefined) return;
            await renderPage(next);
            await runNextPage();
        };

        const workers = Array.from(
            { length: Math.min(MAX_PAGES_IN_FLIGHT, numPages) },
            () => runNextPage()
        );

        await Promise.all(workers);
    });
}


// ===============================
// AVVIO GENERAZIONE (GLOBALE)
// ===============================
document.addEventListener('DOMContentLoaded', async () => {

    const loadingBox = document.getElementById('pdfLoading');
    const pdfContainer = document.getElementById('pdfContainer');
    const pdfLoadingList = document.getElementById('pdfLoadingList');
    const pdfExpandedList = document.getElementById('pdfExpandedList');
    const globalSpinner = document.getElementById('globalSpinner');
    const saveBtn = document.getElementById('saveBtn');

    if (loadingBox) loadingBox.classList.add('hidden');
    if (pdfContainer) pdfContainer.style.display = 'block';

    const items = document.querySelectorAll('.elem');
    if (!items.length) return;

    let loadedPdf = 0;
    const totalPdf = items.length;

    const globalBar = document.getElementById('pdfProgressBar');
    const globalText = document.getElementById('pdfProgressText');

    const updateGlobal = () => {
        const pct = Math.round((loadedPdf / totalPdf) * 100);
        if (globalBar) {
            globalBar.style.width = pct + '%';
            globalBar.setAttribute('aria-valuenow', pct);
        }
        if (globalText) globalText.textContent = pct + '%';
    };

    const onGlobalComplete = () => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            saveBtn.textContent = '💾 Salva impostazione';
        }
        if (globalSpinner) globalSpinner.classList.add('hidden');
    };

    const maybeShowExpanded = () => {
        if (loadedPdf === totalPdf) {
            if (pdfLoadingList) pdfLoadingList.classList.add('hidden');
            if (pdfExpandedList) pdfExpandedList.classList.remove('hidden');
            onGlobalComplete();
        }
    };

    updateGlobal();

    const queue = Array.from(items);
    const processNext = async () => {
        const el = queue.shift();
        if (!el) return;

        const pdfUrl = el.dataset.pdfUrl;
        const fileId = el.dataset.fileId;

        const spinner = document.querySelector(`[data-file-spinner="${fileId}"]`);
        if (spinner) spinner.classList.remove('hidden');

        await generateThumbnails(pdfUrl, 'thumbs-' + fileId, fileId);

        if (spinner) spinner.classList.add('hidden');

        loadedPdf++;
        updateGlobal();
        maybeShowExpanded();

        await processNext();
    };

    const pdfWorkers = Array.from(
        { length: Math.min(MAX_PARALLEL_PDF, totalPdf) },
        () => processNext()
    );

    await Promise.all(pdfWorkers);
});
document.getElementById('formAccesso').addEventListener('submit', function (e) {

    let all = [];
    let order = 0;

    // Prendiamo TUTTE le checkbox selezionate, ovunque siano nel DOM
    document.querySelectorAll('input[type="checkbox"]:checked').forEach(ch => {

        // Estraiamo il file_id dal name pages[ID][]
        const match = ch.name.match(/pages\[(\d+)\]/);
        if (!match) return;

        const fileId = match[1];

        all.push({
            tipo: 'file_pratica',
            file_id: parseInt(fileId),
            pagina_inizio: parseInt(ch.value),
            pagina_fine: parseInt(ch.value),
            ordinamento: order++
        });
    });

    if (all.length === 0) {
        e.preventDefault();
        alert('Seleziona almeno un foglio prima di salvare.');
        return;
    }

    document.getElementById('elementiJson').value = JSON.stringify(all);
});
</script>

@endsection
