@extends('layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
@php
    $filtersOpen = $activeFilters > 0 && !request()->boolean('hide_filters');
    function filterActive(string $name): string {
        return request()->filled($name) ? 'border-yellow-400 ring-1 ring-yellow-300' : '';
    }
@endphp
@php
    function sortLink($column, $label) {
        $currentSort = request('sort');
        $currentDirection = request('direction', 'asc');

        $direction = ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc';

        $arrow = '';
        if ($currentSort === $column) {
            $arrow = $currentDirection === 'asc' ? ' ↑' : ' ↓';
        }

        $params = array_merge(request()->all(), [
            'sort' => $column,
            'direction' => $direction
        ]);

        return '<a href="'.request()->url().'?'.http_build_query($params).'"
                    class="hover:text-blue-600">'.$label.$arrow.'</a>';
    }
@endphp

    <h1 class="text-2xl font-bold mb-6">
        📁 Archivio Pratiche — Dashboard

        @if($activeFilters > 0)
            <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-full dark:bg-blue-500">
                {{ $activeFilters }} filtri attivi
            </span>
        @endif
    </h1>

    {{-- 🔍 Barra filtri --}}
    <form method="GET" class="bg-white shadow rounded-xl border border-gray-200 p-4 space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-lg font-semibold">Filtri</h3>
            <div class="flex items-center gap-3">
                @if($activeFilters > 0)
                    <span class="bg-blue-600 text-white text-xs px-3 py-1 rounded-full dark:bg-blue-500">
                        {{ $activeFilters }} attivi
                    </span>
                @endif
                <button type="button" id="filters-toggle"
                        class="text-sm px-3 py-1 rounded border border-gray-300 bg-gray-100 hover:bg-gray-200">
                    {{ $filtersOpen ? 'Nascondi' : 'Mostra' }} filtri
                </button>
            </div>
        </div>

        <div id="filters-body" class="{{ $filtersOpen ? '' : 'hidden' }} space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Ricerca globale --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Ricerca globale</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                        class="p-2 border rounded w-full {{ filterActive('q') }}"
                        placeholder="Protocollo, cartella, rilascio, oggetto o richiedente">
                </div>

                {{-- Cartella --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Cartella</label>
                    <input type="text" name="cartella" value="{{ request('cartella') ?? request('pratica_id') }}"
                        class="p-2 border rounded w-full {{ filterActive('cartella') ?: filterActive('pratica_id') }}">
                </div>

                {{-- Numero protocollo --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Numero protocollo</label>
                    <input type="text" name="numero_protocollo" value="{{ request('numero_protocollo') }}"
                        class="p-2 border rounded w-full {{ filterActive('numero_protocollo') }}">
                </div>

                {{-- Numero pratica --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Numero pratica</label>
                    <input type="text" name="numero_pratica" value="{{ request('numero_pratica') }}"
                        class="p-2 border rounded w-full {{ filterActive('numero_pratica') }}">
                </div>

                {{-- Numero rilascio --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Numero rilascio</label>
                    <input type="text" name="numero_rilascio" value="{{ request('numero_rilascio') }}"
                        class="p-2 border rounded w-full {{ filterActive('numero_rilascio') }}">
                </div>

                {{-- Anno --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Anno presentazione</label>
                    <input type="number" name="anno" value="{{ request('anno') }}"
                        class="p-2 border rounded w-full {{ filterActive('anno') }}">
                </div>

                {{-- Oggetto --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Oggetto</label>
                    <input type="text" name="oggetto" value="{{ request('oggetto') }}"
                        class="p-2 border rounded w-full {{ filterActive('oggetto') }}">
                </div>

                {{-- Richiedente --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Richiedente</label>
                    <input type="text" name="richiedente"
                        value="{{ request('richiedente') ?? request('cognome') }}"
                        class="p-2 border rounded w-full {{ filterActive('richiedente') ?: filterActive('cognome') }}">
                </div>

                {{-- Tipo pratica --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Tipo pratica</label>
                    <select name="tipo" class="p-2 border rounded w-full {{ filterActive('tipo') }}">
                        <option value="">Tutte</option>
                        @foreach(\App\Models\Pratica::select('sigla_tipo_pratica')->distinct()->pluck('sigla_tipo_pratica') as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo') == $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ✅ Gruppo pratica --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Gruppo pratica</label>
                    <select name="gruppo_pratica" class="p-2 border rounded w-full {{ filterActive('gruppo_pratica') }}">
                        <option value="">Tutti</option>
                        @foreach(
                            \App\Models\Pratica::whereNotNull('gruppo_bat')
                                ->where('gruppo_bat', '!=', '')
                                ->distinct()
                                ->orderBy('gruppo_bat')
                                ->pluck('gruppo_bat') as $gruppo
                        )
                            <option value="{{ $gruppo }}" @selected(request('gruppo_pratica') == $gruppo)>
                                {{ $gruppo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Via --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Via</label>
                    <input type="text" name="via" value="{{ request('via') }}"
                        class="p-2 border rounded w-full {{ filterActive('via') }}">
                </div>

                {{-- Civico --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Civico</label>
                    <input type="text" name="civico" value="{{ request('civico') }}"
                        class="p-2 border rounded w-full {{ filterActive('civico') }}">
                </div>

                {{-- Foglio --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Foglio</label>
                    <input type="text" name="foglio" value="{{ request('foglio') }}"
                        class="p-2 border rounded w-full {{ filterActive('foglio') }}">
                </div>

                {{-- Particella --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Particella / Sub</label>
                    <input type="text" name="particella_sub" value="{{ request('particella_sub') }}"
                        class="p-2 border rounded w-full {{ filterActive('particella_sub') }}">
                </div>

                {{-- PDF --}}
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Testo nei PDF</label>
                    <input type="text" name="pdf" value="{{ request('pdf') }}"
                        class="p-2 border rounded w-full {{ filterActive('pdf') }}">
                </div>

                <label class="flex items-center gap-2 text-sm mt-6">
                    <input type="checkbox" name="vuote" value="1" @checked(request('vuote') == '1')>
                    <span>Solo pratiche senza file</span>
                </label>

                <label class="flex items-center gap-2 text-sm mt-6">
                    <input type="checkbox" name="exact" value="1" @checked(request('exact') == '1')>
                    <span>Filtri esatti</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                🔍 Filtra
            </button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded shadow border bg-gray-100">
                🔄 Reset filtri
            </a>
        </div>
    </form>

    {{-- 📊 Tabella risultati --}}
    <div class="mt-6 bg-white shadow rounded-xl overflow-hidden">
        <table class="w-full text-sm border-collapse">

<thead class="sticky top-0 bg-gray-100 z-20 shadow">
<tr class="text-gray-700 text-xs uppercase tracking-wide">

    <th class="py-3 px-4 text-left w-28">
        {!! sortLink('pratica_id', 'Cartella') !!}
    </th>

    <th class="py-3 px-4 w-28">
        {!! sortLink('numero_pratica', 'N. Pratica') !!}
    </th>

    <th class="py-3 px-4 w-20">
        {!! sortLink('anno_presentazione', 'Anno') !!}
    </th>

    <th class="py-3 px-4 w-52">
        {!! sortLink('richiedenti_completi', 'Richiedente') !!}
    </th>

    <th class="py-3 px-4">
        {!! sortLink('oggetto', 'Oggetto') !!}
    </th>

    <th class="py-3 px-4 w-52">
        {!! sortLink('area_circolazione', 'Via') !!}
    </th>

    {{-- NUOVA COLONNA --}}
    <th class="py-3 px-4 w-28">
        {!! sortLink('sigla_tipo_pratica', 'Tipo') !!}
    </th>

    <th class="py-3 px-4 w-32">
        {!! sortLink('gruppo_bat', 'Gruppo') !!}
    </th>

    <th class="py-3 px-4 text-center w-16">📎</th>
    <th class="py-3 px-4 text-center w-24"></th>

</tr>
</thead>

            <tbody>
            @foreach ($pratiche as $p)
                <tr class="border-b last:border-0 odd:bg-gray-50 hover:bg-blue-50 transition">

                    {{-- Cartella --}}
                    <td class="py-2.5 px-4 font-medium">
                        @php
                            $orig = $p->original_values['pratica_id'] ?? $p->pratica_id;
                            $res  = $p->resolved['pratica_id'] ?? $orig;
                        @endphp
                        {{ $orig }}
                        @if($res !== $orig)
                            <span class="text-red-500 text-xs">({{ $res }})</span>
                        @endif
                    </td>

                    {{-- Numero pratica --}}
                    <td class="py-2.5 px-4">
                        @php
                            $orig = $p->original_values['numero_pratica'] ?? $p->numero_pratica;
                            $res  = $p->resolved['numero_pratica'] ?? $orig;
                        @endphp
                        <span class="text-blue-700 font-semibold">{{ $orig }}</span>
                        @if($res !== $orig)
                            <span class="text-red-500 text-xs">({{ $res }})</span>
                        @endif
                    </td>

                    {{-- Anno --}}
                    <td class="py-2.5 px-4">
                        @php
                            $orig = $p->original_values['anno_presentazione'] ?? $p->anno_presentazione;
                            $res  = $p->resolved['anno_presentazione'] ?? $orig;
                        @endphp
                        {{ $orig }}
                        @if($res !== $orig)
                            <span class="text-red-500 text-xs">({{ $res }})</span>
                        @endif
                    </td>

                    {{-- Richiedente --}}
                    <td class="py-2.5 px-4">
                        {{ $p->richiedenti_completi ?: '—' }}
                        @if($p->richiedenti_resolti && $p->richiedenti_resolti !== $p->richiedenti_completi)
                            <span class="text-red-500 text-xs">({{ $p->richiedenti_resolti }})</span>
                        @endif
                    </td>

                    {{-- Oggetto --}}
                    <td class="py-2.5 px-4 text-[13px] text-gray-700">
                        @php
                            $orig = $p->original_values['oggetto'] ?? $p->oggetto;
                            $res  = $p->resolved['oggetto'] ?? $orig;
                        @endphp
                        <div class="line-clamp-2" title="{{ $res }}">
                            {{ $orig }}
                            @if($res !== $orig)
                                <span class="text-red-500 text-xs">({{ $res }})</span>
                            @endif
                        </div>
                    </td>

                    {{-- Via --}}
                    <td class="py-2.5 px-4">
                        @php
                            $orig = trim(
                                ($p->original_values['area_circolazione'] ?? $p->area_circolazione) . ' ' .
                                ($p->original_values['civico_esponente'] ?? $p->civico_esponente)
                            );
                            $res = trim(
                                ($p->resolved['area_circolazione'] ?? $p->area_circolazione) . ' ' .
                                ($p->resolved['civico_esponente'] ?? $p->civico_esponente)
                            );
                        @endphp
                        {{ $orig }}
                        @if($res !== $orig)
                            <span class="text-red-500 text-xs">({{ $res }})</span>
                        @endif
                    </td>

                    {{-- Tipo pratica --}}
                    <td class="py-2.5 px-4 text-xs font-semibold text-gray-700">
                        {{ $p->sigla_tipo_pratica ?? '—' }}
                    </td>

                    {{-- Gruppo BAT --}}
                    <td class="py-2.5 px-4 font-mono text-xs">
                        @if($p->gruppo_bat)
                    <a href="{{ route('dashboard', [
                        'gruppo_pratica' => $p->gruppo_bat,
                        'hide_filters' => 1,
                    ]) }}"
                    class="text-blue-600 hover:text-blue-800 underline"
                    title="Mostra tutte le pratiche del gruppo {{ $p->gruppo_bat }}">
                        {{ $p->gruppo_bat }}
                    </a>
                        @else
                            —
                        @endif
                    </td>

                    {{-- Stato file --}}
                    <td class="py-2.5 px-4 text-center">
                        @if ($p->files_count == 0)
                            <span class="text-red-500 text-xl">●</span>
                        @else
                            <span class="text-green-600 text-xl">●</span>
                        @endif
                    </td>

                    {{-- Azione --}}
                    <td class="py-2.5 px-4 text-center">
                        <a href="/pratica/{{ $p->id }}"
                        onclick="localStorage.setItem('dashboardReturn', window.location.search)"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded shadow">
                            Apri →
                        </a>
                    </td>

                </tr>
            @endforeach
            </tbody>

        </table>

        <div class="px-4 py-3 bg-gray-50">
            {{ $pratiche->withQueryString()->links() }}
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('filters-toggle');
    const filters   = document.getElementById('filters-body');

    if (!toggleBtn || !filters) {
        console.warn('Toggle filtri: elementi non trovati');
        return;
    }

    toggleBtn.addEventListener('click', function () {
        const isHidden = filters.classList.contains('hidden');

        filters.classList.toggle('hidden');

        toggleBtn.textContent = isHidden
            ? 'Nascondi filtri'
            : 'Mostra filtri';
    });
});
</script>

@endsection
