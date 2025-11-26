<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pratica {{ $p->numero_pratica }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- PDF.js v2.16.105 (funziona con namespace pdfjsLib) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf_viewer.min.css" />

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
    </script>

    <!-- mark.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/mark.min.js"></script>
</head>

<body class="p-4">

<a href="/dashboard" class="btn btn-secondary mb-3">⬅ Torna alla dashboard</a>

<h1 class="mb-0">Pratica {{ $p->numero_pratica }}</h1>
<h4 class="text-muted">{{ $p->oggetto }}</h4>

@php
$search = request('pdf');
$requestedFile = request('file'); // nome PDF arrivato dal badge
$autoPdf = null;

// 1️⃣ Se esiste "file=" nel link → priorità assoluta
if ($requestedFile) {
    foreach ($pdfFiles as $file) {
        if ($file['name'] === $requestedFile) {
            $autoPdf = $file['url'];
            break;
        }
    }
}

// 2️⃣ Altrimenti, usa il match di ricerca nei PDF
if (!$autoPdf && $search) {
    foreach ($pdfFiles as $file) {
        if (stripos($file['content'] ?? '', $search) !== false) {
            $autoPdf = $file['url'];
            break;
        }
    }
}
@endphp

<hr>

<h3>📂 Dettagli della pratica</h3>

<div class="accordion mb-4" id="accordionPratica">

    <!-- DATI PRINCIPALI -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingMain">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseMain" aria-expanded="true" aria-controls="collapseMain">
                📌 Dati principali
            </button>
        </h2>
        <div id="collapseMain" class="accordion-collapse collapse show" aria-labelledby="headingMain" data-bs-parent="#accordionPratica">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><b>Tipo pratica:</b> {{ $p->sigla_tipo_pratica }}</p>
                        <p><b>Anno presentazione:</b> {{ $p->anno_presentazione }}</p>
                        <p><b>Riferimento libero:</b> {{ $p->riferimento_libero }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><b>Data protocollo:</b> {{ $p->data_protocollo }}</p>
                        <p><b>Numero protocollo:</b> {{ $p->numero_protocollo }}</p>
                        <p><b>Pratica ID interno:</b> {{ $p->pratica_id }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RILASCIO -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingRilascio">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseRilascio" aria-expanded="false" aria-controls="collapseRilascio">
                📑 Rilascio
            </button>
        </h2>
        <div id="collapseRilascio" class="accordion-collapse collapse" aria-labelledby="headingRilascio" data-bs-parent="#accordionPratica">
            <div class="accordion-body">
                <p><b>Data rilascio:</b> {{ $p->data_rilascio }}</p>
                <p><b>Numero rilascio:</b> {{ $p->numero_rilascio }}</p>
            </div>
        </div>
    </div>

    <!-- LOCALIZZAZIONE -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingLocal">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseLocal" aria-expanded="false" aria-controls="collapseLocal">
                📬 Localizzazione
            </button>
        </h2>
        <div id="collapseLocal" class="accordion-collapse collapse" aria-labelledby="headingLocal" data-bs-parent="#accordionPratica">
            <div class="accordion-body">
                <p><b>Via:</b> {{ $p->area_circolazione }}</p>
                <p><b>Civico:</b> {{ $p->civico_esponente }}</p>
            </div>
        </div>
    </div>

    <!-- DATI CATASTALI -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingCatasto">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseCatasto" aria-expanded="false" aria-controls="collapseCatasto">
                📍 Dati catastali
            </button>
        </h2>
        <div id="collapseCatasto" class="accordion-collapse collapse" aria-labelledby="headingCatasto" data-bs-parent="#accordionPratica">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><b>Foglio:</b> {{ $p->foglio }}</p>
                        <p><b>Particella / Sub:</b> {{ $p->particella_sub }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><b>Sezione:</b> {{ $p->sezione }}</p>
                        <p><b>Tipo catasto:</b> {{ $p->tipo_catasto }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><b>Codice catasto:</b> {{ $p->codice_catasto }}</p>
                        <p><b>Nota:</b> {{ $p->nota }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>


<hr>

{{-- ===========================
     DOCUMENTI PDF
   =========================== --}}
<h3 class="mb-3">📄 Documenti associati</h3>

@if (count($pdfFiles) === 0)
    <p class="text-muted">Nessun documento trovato nella cartella <b>{{ $p->cartella }}</b>.</p>
@else
    <ul class="list-group mb-4">
        @foreach ($pdfFiles as $pdf)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                {{ $pdf['name'] }}
                <a href="{{ $pdf['url'] }}" target="_blank" class="btn btn-sm btn-danger pdf-link">
                    Apri PDF
                </a>
            </li>
        @endforeach
    </ul>
@endif

@if (count($pdfFiles) > 0)
    <h4>👁️ Anteprima documento</h4>
    <p class="text-muted">Clicca un file per visualizzarlo qui sotto.</p>

    <div id="pdfViewerContainer" class="border" style="height: 800px; overflow-y: scroll;">
        <div id="pdfViewer" class="pdfViewer"></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($autoPdf)
            document.getElementById('pdfViewer').src = "{{ $autoPdf }}";
        @endif
    });
    </script>

    <script>
        document.querySelectorAll('.pdf-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('pdfViewer').src = this.href;
            });
        });
    </script>

    <script>
    const url = @json($autoPdf);
    const searchTerm = @json(request('pdf')); // parola cercata dalla dashboard

    if (!url) {
        console.warn("Nessun PDF da mostrare");
    } else {
        renderPdf(url, searchTerm);
    }

    async function renderPdf(url, term) {
        const loadingTask = pdfjsLib.getDocument(url);
        const pdf = await loadingTask.promise;

        const viewer = document.getElementById("pdfViewer");
        viewer.innerHTML = ''; // reset

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum);

            const viewport = page.getViewport({ scale: 1.4 });

            const pageDiv = document.createElement("div");
            pageDiv.className = "pdf-page mb-3";
            pageDiv.style.position = "relative";

            // Canvas per il rendering
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            pageDiv.appendChild(canvas);

            // Layer testo PDF.js
            const textLayerDiv = document.createElement("div");
            textLayerDiv.className = "textLayer";
            pageDiv.appendChild(textLayerDiv);

            viewer.appendChild(pageDiv);

            // Render pagina
            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;

            // Render testo (necessario per highlight)
            const textContent = await page.getTextContent();
            pdfjsLib.renderTextLayer({
                textContent,
                container: textLayerDiv,
                viewport,
                textDivs: []
            });

            // Applichiamo highlight dopo il render
            if (term) {
                setTimeout(() => {
                    const marker = new Mark(textLayerDiv);
                    marker.mark(term, {
                        separateWordSearch: true,
                        className: 'pdf-highlight'
                    });
                }, 200); // leggero delay per sicurezza
            }
        }
    }

    // Highlight styling
    const style = document.createElement('style');
    style.textContent = `
    .pdf-highlight {
        background: yellow !important;
        color: black !important;
    }
    .textLayer > div {
        mix-blend-mode: multiply; /* rende il testo evidenziato migliore */
    }
    `;
    document.head.appendChild(style);
    </script>

@endif

<hr class="my-4">

<button class="btn btn-primary" onclick="window.location='/voice.html'">
    🎤 Cerca con la voce
</button>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
