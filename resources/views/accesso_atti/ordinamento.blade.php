@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded">

    <h1 class="text-xl font-bold mb-4">✏️ Modifica ordine pagine – Fascicolo {{ $accesso->id }}</h1>

    <p class="text-gray-600 mb-4">
        Trascina gli elementi per cambiare l’ordine. Ogni riga corrisponde a UNA pagina del fascicolo finale.
    </p>

    <form method="POST" action="{{ route('accesso-atti.ordinamento.salva', $accesso->id) }}">
        @csrf

        <ul id="sortable" class="space-y-2">
            @foreach($accesso->elementi->sortBy('ordinamento') as $el)
                <li class="p-3 bg-gray-50 border rounded cursor-move" draggable="true"
                    data-id="{{ $el->id }}">
                    <strong>{{ $el->file->file }}</strong>
                    — pagina {{ $el->pagina_inizio }}
                </li>
            @endforeach
        </ul>

        <input type="hidden" name="ordine" id="ordineInput">

        <button class="mt-4 px-4 py-2 bg-green-600 text-white rounded shadow">
            💾 Salva nuovo ordine
        </button>

    </form>
</div>

<script>
let dragged;

document.querySelectorAll('#sortable li').forEach(el => {
    el.addEventListener('dragstart', () => dragged = el);
    el.addEventListener('dragover', e => e.preventDefault());
    el.addEventListener('drop', e => {
        e.preventDefault();
        if (dragged !== el) {
            el.parentNode.insertBefore(dragged, el);
        }
    });
});

document.querySelector('form').addEventListener('submit', function() {
    let ordine = [];
    document.querySelectorAll('#sortable li').forEach(li => {
        ordine.push(li.dataset.id);
    });
    document.getElementById('ordineInput').value = JSON.stringify(ordine);
});
</script>

@endsection
