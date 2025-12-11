@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded-lg">

    <h1 class="text-2xl font-bold mb-6">📘 Nuovo Fascicolo – Pratica {{ $praticaId }}</h1>

    <form id="formAccesso" method="POST" action="{{ route('accesso-atti.store', $praticaId) }}">
        @csrf

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
                            <p class="text-sm text-gray-500">Fogli totali: {{ $f->num_pagine }}</p>
                        </div>

                        <button type="button"
                                onclick="openPreview('{{ $pdfUrl }}')"
                                class="px-3 py-1 text-sm bg-blue-600 text-white rounded shadow">
                            Anteprima
                        </button>
                    </div>

                    {{-- THUMBNAILS --}}
                    <div id="thumbs-{{ $f->id }}"
                         class="flex flex-wrap gap-3 text-center text-sm text-gray-600">
                        Caricamento miniature...
                    </div>

                </div>
            @endforeach

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
// --- Preview PDF ---
function openPreview(url) {
    document.getElementById('pdfFrame').src = url;
    document.getElementById('modalPdf').classList.remove('hidden');
}
function closePreview() {
    document.getElementById('modalPdf').classList.add('hidden');
}

// --- Drag & Drop ordering ---
let dragged = null;
const lista = document.getElementById('lista');

lista.addEventListener('dragstart', e => dragged = e.target.closest('.elem'));
lista.addEventListener('dragover', e => e.preventDefault());
lista.addEventListener('drop', e => {
    e.preventDefault();
    const target = e.target.closest('.elem');
    if (target && dragged && dragged !== target) {
        lista.insertBefore(dragged, target);
    }
});

// --- Submit JSON (solo pagine selezionate) ---
document.getElementById('formAccesso').addEventListener('submit', function() {
    let all = [];
    let order = 0;

    document.querySelectorAll('.elem').forEach(el => {
        const fileId = el.dataset.fileId;
        const checks = el.querySelectorAll('input[type="checkbox"]:checked');

        checks.forEach(ch => {
            all.push({
                tipo: 'file_pratica',
                file_id: fileId,
                pagina_inizio: ch.value,
                pagina_fine: ch.value,
                ordinamento: order++
            });
        });
    });

    document.getElementById('elementiJson').value = JSON.stringify(all);
});


// --- Thumbnail generator using PDF.js ---
async function generateThumbnails(pdfUrl, containerId, fileId) {

    const container = document.getElementById(containerId);
    container.innerHTML = "";

    const pdf = await pdfjsLib.getDocument(pdfUrl).promise;

    for(let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {

        const page = await pdf.getPage(pageNum);

        const viewport = page.getViewport({ scale: 0.22 });
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        await page.render({ canvasContext: ctx, viewport: viewport }).promise;

        // wrapper thumbnail
        const wrapper = document.createElement("label");
        wrapper.className = "thumb-wrapper inline-block cursor-pointer";

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.value = pageNum;
        checkbox.name = `pages[${fileId}][]`;
        checkbox.className = "block mx-auto mt-1";

        checkbox.addEventListener("change", () => {
            if (checkbox.checked) wrapper.classList.add("thumb-selected");
            else wrapper.classList.remove("thumb-selected");
        });

        wrapper.appendChild(canvas);
        wrapper.appendChild(checkbox);

        container.appendChild(wrapper);
    }
}

// --- dopo che tutto è definito, genera thumbnails per TUTTI i file ---
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.elem').forEach(el => {
        const fileId = el.dataset.fileId;
        const pdfUrl = el.dataset.pdfUrl;
        const containerId = 'thumbs-' + fileId;
        generateThumbnails(pdfUrl, containerId, fileId);
    });
});
</script>

@endsection
