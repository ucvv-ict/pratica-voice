<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pratica {{ $p->numero_pratica }}</title>

    {{-- Solo CSS di Bootstrap per avere una grafica decente --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Un minimo di stile per i <details> */
        details {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: #fafafa;
        }
        details > summary {
            cursor: pointer;
            font-weight: 600;
        }
        details[open] {
            background: #f0f4ff;
        }
    </style>
</head>

<body class="p-4">

@if(session('error'))
    <div class="alert alert-danger">
        ❌ {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        ✅ {{ session('success') }}
    </div>
@endif

<a href="/dashboard" class="btn btn-secondary mb-3">⬅ Torna alla dashboard</a>

<h1 class="mb-0">Pratica {{ $p->numero_pratica }}</h1>
<h4 class="text-muted">{{ $p->oggetto }}</h4>

<hr>

@php
    $search       = request('pdf');   // testo cercato nei PDF dalla dashboard
    $requestedFile = request('file'); // nome PDF passato dal badge giallo
    $autoPdf      = null;

    // 1️⃣ Se nel link c'è file=... → priorità assoluta
    if ($requestedFile) {
        foreach ($pdfFiles as $file) {
            if ($file['name'] === $requestedFile) {
                $autoPdf = $file['url'];
                break;
            }
        }
    }

    // 2️⃣ Se non c'è file, ma c'è ricerca pdf → prendi il primo PDF che contiene il testo
    if (!$autoPdf && $search) {
        foreach ($pdfFiles as $file) {
            if (stripos($file['content'] ?? '', $search) !== false) {
                $autoPdf = $file['url'];
                break;
            }
        }
    }
@endphp

{{-- ===========================
     RICHIEDENTI (COLLAPSIBLE)
   =========================== --}}
<details open>
    <summary>🧑‍💼 Richiedenti</summary>
    <div class="mt-2 row">
        <div class="col-md-6">
            <p><b>1)</b> {{ $p->rich_cognome1 }} {{ $p->rich_nome1 }}</p>
            @if($p->rich_cognome2 || $p->rich_nome2)
                <p><b>2)</b> {{ $p->rich_cognome2 }} {{ $p->rich_nome2 }}</p>
            @endif
            @if($p->rich_cognome3 || $p->rich_nome3)
                <p><b>3)</b> {{ $p->rich_cognome3 }} {{ $p->rich_nome3 }}</p>
            @endif
        </div>
    </div>
</details>

{{-- ===========================
     DATI PRINCIPALI
   =========================== --}}
<details open>
    <summary>📌 Dati principali</summary>
    <div class="mt-2 row">
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
</details>

{{-- ===========================
     RILASCIO
   =========================== --}}
<details>
    <summary>📑 Rilascio</summary>
    <div class="mt-2 row">
        <div class="col-md-6">
            <p><b>Data rilascio:</b> {{ $p->data_rilascio }}</p>
            <p><b>Numero rilascio:</b> {{ $p->numero_rilascio }}</p>
        </div>
    </div>
</details>

{{-- ===========================
     LOCALIZZAZIONE
   =========================== --}}
<details>
    <summary>📬 Localizzazione</summary>
    <div class="mt-2 row">
        <div class="col-md-6">
            <p><b>Via:</b> {{ $p->area_circolazione }}</p>
            <p><b>Civico:</b> {{ $p->civico_esponente }}</p>
        </div>
    </div>
</details>

{{-- ===========================
     DATI CATASTALI
   =========================== --}}
<details>
    <summary>📍 Dati catastali</summary>
    <div class="mt-2 row">
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
</details>

<hr>

{{-- ===========================
     DOCUMENTI PDF
   =========================== --}}
<h3 class="mb-3">📄 Documenti associati</h3>

@if (count($pdfFiles) === 0)
    <p class="text-muted">
        Nessun documento trovato nella cartella <b>{{ $p->cartella }}</b>.
    </p>
@else
<form method="POST" action="/pratica/{{ $p->id }}/zip">
    @csrf

    <div class="d-flex justify-content-between mb-2">
        <button type="button" id="selectAll" class="btn btn-sm btn-secondary">
            Seleziona tutti
        </button>
        <button type="button" id="deselectAll" class="btn btn-sm btn-secondary">
            Deseleziona tutti
        </button>

        <div class="d-flex align-items-center gap-2">
            <button type="submit" id="zipBtn" class="btn btn-sm btn-success" disabled>
                📦 Scarica ZIP selezionati (<span id="zipCount">0</span>)
            </button>
            <span id="zipLoading" class="text-muted d-none">
                <span class="spinner-border spinner-border-sm"></span>
                Preparazione ZIP...
            </span>
        </div>
    </div>

    <ul class="list-group mb-4">
        @foreach ($pdfFiles as $pdf)
            <li class="list-group-item d-flex justify-content-between align-items-center">

                <div>
                    <input type="checkbox"
                        class="pdf-check"
                        name="files[]"
                        value="{{ $pdf['name'] }}">
                    {{ $pdf['name'] }}
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ $pdf['url'] }}"
                       class="btn btn-sm btn-primary pdf-link">
                        👁️ Anteprima
                    </a>

                    <a href="{{ $pdf['url'] }}" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        🔗 Apri
                    </a>
                </div>

            </li>
        @endforeach
    </ul>
</form>

<script>
const zipForm = document.querySelector('form[action$="/zip"]');
const zipBtn = document.getElementById('zipBtn');

function refreshZipButton() {
    const checkboxes = [...document.querySelectorAll('.pdf-check')];
    const selected = checkboxes.filter(cb => cb.checked).length;

    // aggiorna numero nel bottone
    document.getElementById('zipCount').textContent = selected;

    // abilita/disabilita il bottone
    zipBtn.disabled = (selected === 0);
}

// Seleziona tutti
document.getElementById('selectAll').onclick = () => {
    document.querySelectorAll('.pdf-check').forEach(cb => cb.checked = true);
    refreshZipButton();
};

// Deseleziona tutti
document.getElementById('deselectAll').onclick = () => {
    document.querySelectorAll('.pdf-check').forEach(cb => cb.checked = false);
    refreshZipButton();
};

// Aggiorna ad ogni click
document.querySelectorAll('.pdf-check').forEach(cb => {
    cb.addEventListener('change', refreshZipButton);
});

// Spinner + spegnimento
zipForm.addEventListener('submit', () => {
    const loader = document.getElementById('zipLoading');
    if (loader) loader.classList.remove('d-none');

    // Spegni spinner dopo 4 secondi
    setTimeout(() => {
        if (loader) loader.classList.add('d-none');
    }, 4000);
});

// Stato iniziale coerente
refreshZipButton();
</script>
@endif

@if (count($pdfFiles) > 0)
    <h4>👁️ Anteprima documento</h4>
    <p class="text-muted">
        @if($search)
            Stai visualizzando la pratica filtrata per <b>"{{ $search }}"</b>.
            @if($autoPdf)
                È stato aperto in automatico il primo PDF che contiene il testo.
            @else
                Nessun PDF di questa pratica contiene esattamente quel testo.
            @endif
        @else
            Clicca un PDF per visualizzarlo qui sotto.
        @endif
    </p>

    <iframe
        id="pdfViewer"
        src="{{ $autoPdf ?? '' }}"
        width="100%"
        height="800"
        style="border:1px solid #ccc; border-radius:4px;">
    </iframe>

    <script>
        // Cambia PDF nell'iframe quando clicchi "Anteprima"
        document.querySelectorAll('.pdf-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const viewer = document.getElementById('pdfViewer');
                viewer.src = this.href;
            });
        });
    </script>
@endif

<hr class="my-4">

<button class="btn btn-primary" onclick="window.location='/voice.html'">
    🎤 Cerca con la voce
</button>


</body>
</html>
