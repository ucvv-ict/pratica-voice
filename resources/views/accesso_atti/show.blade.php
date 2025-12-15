@extends('layout')

@section('content')
{{-- TORNA ALLA PRATICA --}}
<a href="{{ route('pratica.show', $accesso->pratica_id) }}"
   class="inline-block mb-4 px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded">
    ⬅ Torna alla pratica
</a>

<div class="bg-white shadow p-6 rounded-lg">
    @php
        $r2Enabled = env('R2_BUCKET');
        $r2Active = $accesso->r2_link && $accesso->r2_link_expires_at && \Carbon\Carbon::parse($accesso->r2_link_expires_at)->isFuture();
    @endphp

    {{-- TITOLO --}}
    <h1 class="text-2xl font-bold mb-2 flex items-center gap-2">
        📘 Fascicolo Accesso Atti – Versione {{ $accesso->versione }}
    </h1>

    {{-- INFO PRATICA --}}
    <p class="text-gray-600 mb-6 leading-6">
        <strong>Pratica:</strong> {{ $accesso->pratica_id }} <br>
        <strong>Creato il:</strong> {{ $accesso->created_at->format('d/m/Y H:i') }} <br>
        <strong>Descrizione:</strong> {{ $accesso->descrizione ?: '—' }}
        <strong>Note:</strong> {{ $accesso->note ?: '—' }}
    </p>

    @if($accesso->r2_link)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <div class="font-semibold text-blue-900">Link R2 generato</div>
            <div class="text-sm text-blue-800 break-all">
                <a href="{{ $accesso->r2_link }}" target="_blank" class="underline">{{ $accesso->r2_link }}</a>
            </div>
            <div class="text-xs text-gray-600 mt-1">
                Generato il {{ optional($accesso->r2_link_generated_at)->format('d/m/Y H:i') ?? '—' }}
                @if($accesso->r2_link_expires_at)
                    • Scade il {{ \Carbon\Carbon::parse($accesso->r2_link_expires_at)->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>
    @endif

    <details class="mb-6 border border-gray-300 rounded p-4 bg-gray-50">
        <summary class="cursor-pointer font-semibold mb-3">✏️ Modifica descrizione e note</summary>

        <form method="POST" action="{{ route('accesso-atti.update', $accesso->id) }}" class="mt-3">
            @csrf
            @method('PUT')

            {{-- Descrizione --}}
            <label class="block text-sm font-semibold mb-1">Descrizione</label>
            <input type="text"
                name="descrizione"
                class="w-full border-gray-300 rounded mb-4"
                value="{{ old('descrizione', $accesso->descrizione) }}">

            {{-- Note --}}
            <label class="block text-sm font-semibold mb-1">Note</label>
            <textarea name="note"
                    rows="2"
                    class="w-full border-gray-300 rounded mb-4"
                    placeholder="Annotazioni interne">{{ old('note', $accesso->note) }}</textarea>

            <button class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700">
                💾 Salva modifiche
            </button>
        </form>
    </details>

    {{-- AZIONI PRINCIPALI --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">

        <a href="{{ route('accesso-atti.download', $accesso->id) }}"
        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded shadow">
            ⬇ Scarica PDF generato
        </a>

        <button onclick="document.getElementById('previewPanel').classList.toggle('hidden')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow">
            👁️ Anteprima fascicolo
        </button>

        <button id="r2Btn"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded shadow {{ $r2Enabled && !$r2Active ? '' : 'opacity-50 cursor-not-allowed' }}"
                onclick="openR2Modal()"
                {{ $r2Enabled && !$r2Active ? '' : 'disabled' }}>
            📤 {{ $r2Active ? 'Link R2 già generato' : 'Invia link R2' }}
        </button>

        <a href="{{ route('accesso-atti.ordinamento', $accesso->id) }}"
        class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded shadow">
            ✏️ Modifica ordine pagine
        </a>

        {{-- 🔥 ELIMINA VERSIONE --}}
        <form action="{{ route('accesso-atti.destroy', $accesso->id) }}"
            method="POST"
            onsubmit="return confirm('Vuoi davvero eliminare questa versione?');">
            @csrf
            @method('DELETE')

            <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded shadow">
                🗑️ Elimina
            </button>
        </form>
    </div>

    {{-- SELECT VERSIONI --}}
    <div class="mb-8">
        <label class="font-semibold text-gray-700">Versioni disponibili:</label>

        <select onchange="location.href='/accesso-atti/' + this.value"
                class="border rounded px-3 py-2 ml-2">
            @foreach($tutteVersioni as $v)
                <option value="{{ $v->id }}"
                    {{ $v->id == $accesso->id ? 'selected' : '' }}>
                    Versione {{ $v->versione }} — {{ $v->created_at->format('d/m/Y H:i') }}
                </option>
            @endforeach
        </select>
    </div>

{{-- ANTEPRIMA INCORPORATA --}}
    <div id="previewPanel" class="hidden mt-4">
        <iframe src="{{ route('accesso-atti.preview.inline', $accesso->id) }}"
                class="w-full"
                style="height:85vh; border:1px solid #ccc; border-radius:8px;">
        </iframe>
    </div>


    <hr class="my-6">

    {{-- MODALE R2 --}}
    <div id="r2Modal" class="fixed inset-0 hidden items-center justify-center z-50">
        <div class="absolute inset-0 bg-black opacity-40" onclick="closeR2Modal()"></div>
        <div class="relative bg-white rounded-lg shadow-lg p-6 w-11/12 md:w-2/3 lg:w-1/2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Invio con Cloudflare R2</h3>
                <button class="text-gray-600 hover:text-gray-800" onclick="closeR2Modal()">✖</button>
            </div>
            <div id="r2Status" class="text-sm text-gray-700 mb-3">
                Generazione PDF in corso...
            </div>
            <div id="r2Progress" class="flex items-center gap-2 mb-4">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span id="r2Step">Preparazione...</span>
            </div>
            <div id="r2Result" class="hidden">
                <p class="text-sm text-gray-800 mb-2">Link generato:</p>
                <a id="r2Link" href="#" target="_blank" class="text-blue-700 underline break-all"></a>
                <button id="copyR2"
                        class="ml-2 px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300"
                        onclick="copyR2Link()">
                    Copia
                </button>
            </div>
            <div id="r2Error" class="hidden text-sm text-red-600 mt-2"></div>
        </div>
    </div>

    {{-- DOCUMENTI INCLUSI --}}
    <h2 class="text-xl font-semibold mb-3">Documenti inclusi</h2>

    <div class="space-y-4">

        @foreach($accesso->elementi as $el)
                
            <div class="p-4 border rounded bg-gray-50 hover:bg-gray-100 transition">
                <div class="font-semibold text-blue-900">
                    {{ $el->file->file }}
                </div>

                <div class="text-sm text-gray-600">
                    Fogli inclusi: pagina {{ $el->pagina_inizio }} → {{ $el->pagina_fine }}
                </div>
            </div>

        @endforeach

    </div>

</div>

<script>
    const sendModal = document.getElementById('r2Modal');
    const sendStatus = document.getElementById('r2Status');
    const sendStep = document.getElementById('r2Step');
    const sendProgress = document.getElementById('r2Progress');
    const sendResult = document.getElementById('r2Result');
    const sendError = document.getElementById('r2Error');
    const sendLink = document.getElementById('r2Link');
    const sendBtn = document.getElementById('r2Btn');
    const sendEnabled = {{ $r2Enabled ? 'true' : 'false' }};

    function openR2Modal() {
        if (!sendEnabled) {
            alert('Cloudflare R2 non è configurato.');
            return;
        }
        sendModal.classList.remove('hidden');
        sendModal.classList.add('flex');
        sendStatus.textContent = 'Generazione PDF in corso...';
        sendStep.textContent = 'Preparazione...';
        sendProgress.classList.remove('hidden');
        sendResult.classList.add('hidden');
        sendError.classList.add('hidden');
        sendError.textContent = '';
        sendBtn.disabled = true;

        fetch('{{ route('accesso-atti.r2', $accesso->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.link) {
                throw new Error(data.error || 'Errore imprevisto durante l\'upload.');
            }
            sendStatus.textContent = 'Upload completato con successo.';
            sendStep.textContent = 'Completato';
            sendProgress.classList.add('hidden');
            sendResult.classList.remove('hidden');
            sendLink.textContent = data.link;
            sendLink.href = data.link;
        }).catch(err => {
            sendProgress.classList.add('hidden');
            sendError.classList.remove('hidden');
            sendError.textContent = err.message;
            sendStatus.textContent = 'Errore durante l\'invio.';
        }).finally(() => {
            sendBtn.disabled = false;
        });
    }

    function closeR2Modal() {
        sendModal.classList.add('hidden');
        sendModal.classList.remove('flex');
    }

    function copyR2Link() {
        if (!sendLink.href) return;
        navigator.clipboard.writeText(sendLink.href);
    }
</script>

@endsection
