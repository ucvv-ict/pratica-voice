@extends('layout')

@section('content')

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
    </p>

    {{-- AZIONI PRINCIPALI --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">

        {{-- DOWNLOAD (GENERA PDF LIVE) --}}
        <a href="{{ route('accesso-atti.download', $accesso->id) }}"
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded shadow">
            ⬇ Scarica PDF generato
        </a>

        {{-- TOGGLE ANTEPRIMA --}}
        <button onclick="document.getElementById('previewPanel').classList.toggle('hidden')"
                class="px-4 py-2 bg-blue-600 text-white rounded shadow">
            👁️ Anteprima fascicolo
        </button>

        {{-- MODIFICA ORDINE PAGINE --}}
        <a href="{{ route('accesso-atti.ordinamento', $accesso->id) }}"
           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded shadow">
            ✏️ Modifica ordine pagine
        </a>
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


    {{-- TIMELINE VERSIONI --}}
    <div class="border-l-4 border-blue-500 pl-4 mb-10">

        @foreach($tutteVersioni as $v)
            <div class="mb-4">
                <div class="text-sm text-gray-500">{{ $v->created_at->format('d/m/Y H:i') }}</div>

                <a href="{{ route('accesso-atti.show', $v->id) }}"
                    class="text-blue-700 font-semibold hover:underline">
                    Versione {{ $v->versione }}
                </a>

                <div class="text-gray-600 text-sm">
                    Documenti: {{ $v->elementi->count() }}
                    @if($v->descrizione)
                        — {{ $v->descrizione }}
                    @endif
                </div>
            </div>
        @endforeach

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
