@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded-lg">

    <h1 class="text-2xl font-bold mb-6">📘 Nuovo Fascicolo – Pratica {{ $praticaId }}</h1>

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

        {{-- NOTE --}}
        <label class="block text-sm font-semibold mb-1">Note (facoltative)</label>
        <textarea name="note"
                rows="2"
                class="w-full border-gray-300 rounded-md shadow-sm mb-6"
                placeholder="Annotazioni interne, informazioni utili, ecc."></textarea>

        <h2 class="text-lg font-bold mb-2">Documenti della pratica</h2>
        <p class="text-gray-600 mb-4">Trascina per ordinare. Seleziona i fogli che vuoi includere.</p>

        <div id="pdfContainer" style="max-height: 70vh; overflow-y: auto; display: none;">
            <div class="progress mb-3" style="height: 0.75rem;">
                <div id="pdfProgressBar"
                     class="progress-bar"
                     role="progressbar"
                     style="width: 0%;"
                     aria-valuenow="0"
                     aria-valuemin="0"
                     aria-valuemax="100"></div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Caricamento PDF: <span id="pdfProgressText">0%</span></p>

            <div id="lista" class="space-y-6">

            @foreach($files as $f)
                @php
                    $pdfUrl = $f->public_url;
                @endphp

                <div class="elem bg-gray-50 p-4 rounded border shadow-sm"
                     draggable="true"
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

                        <div style="width: 200px;">
                            <div class="progress" style="height: 0.6rem;">
                                <div class="progress-bar"
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

                        <button type="button"
                                onclick="openPreview('{{ $pdfUrl }}')"
                                class="px-3 py-1 text-sm bg-blue-600 text-white rounded shadow">
                            Anteprima
                        </button>
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

        <input type="hidden" name="elementi" id="elementiJson">

        <button type="submit"
                class="mt-6 px-5 py-3 bg-green-600 text-white font-semibold rounded shadow hover:bg-green-700">
            💾 Salva impostazione
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
        const loadedNodes = [];

        const updateProgress = () => {
            const pct = Math.round((loaded / numPages) * 100);
            if (progressBar) {
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
                progressBar.setAttribute('aria-valuenow', pct);
            }
        };

        updateProgress();

        for (let pageNum = 1; pageNum <= numPages; pageNum++) {

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

            img.onload = () => {
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
                loadedNodes.push(wrapper);

                loaded++;
                updateProgress();

                // 👉 SOLO QUI mostriamo TUTTO INSIEME
                if (loaded === numPages) {
                    container.innerHTML = '';
                    loadedNodes.forEach(n => container.appendChild(n));
                    container.classList.remove('hidden');

                    if (progressBar) {
                        progressBar.classList.add('bg-success');
                        progressBar.textContent = '✔ Caricato';
                    }
                    resolve();
                }
            };
        }
    });
}


// ===============================
// AVVIO GENERAZIONE (GLOBALE)
// ===============================
document.addEventListener('DOMContentLoaded', async () => {

    const loadingBox = document.getElementById('pdfLoading');
    const pdfContainer = document.getElementById('pdfContainer');

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

    updateGlobal();

    // ⚠️ sequenziale per non saturare CPU
    for (const el of items) {
        const pdfUrl = el.dataset.pdfUrl;
        const fileId = el.dataset.fileId;

        await generateThumbnails(pdfUrl, 'thumbs-' + fileId, fileId);

        loadedPdf++;
        updateGlobal();
    }
});
</script>

@endsection
