@extends('layout')

@section('content')

<div class="bg-white shadow p-6 rounded-lg">

    <h1 class="text-xl font-bold mb-4">
        📄 Anteprima Fascicolo – Versione {{ $accesso->versione }}
    </h1>

    <iframe
        src="data:application/pdf;base64,{{ $pdfBase64 }}"
        class="w-full"
        style="height:85vh; border:1px solid #ccc; border-radius:8px;">
    </iframe>

    <div class="mt-4">
        <a href="{{ route('accesso-atti.show', $accesso->id) }}"
           class="px-4 py-2 bg-blue-600 text-white rounded shadow">
            ← Torna al fascicolo
        </a>
    </div>

</div>

@endsection
