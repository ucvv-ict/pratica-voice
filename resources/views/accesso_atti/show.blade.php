@extends('layout')

@section('content')
{{-- TORNA ALLA PRATICA --}}
<a href="{{ route('pratica.show', $accesso->pratica_id) }}"
   class="inline-block mb-4 px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded">
    ⬅ Torna alla pratica
</a>

<div class="bg-white shadow p-6 rounded-lg">

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

@endsection
