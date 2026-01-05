@extends('layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
@php
    $filtersOpen = $activeFilters > 0;
    function filterActive(string $name): string {
        return request()->filled($name) ? 'border-yellow-400 ring-1 ring-yellow-300' : '';
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
                        class="text-sm px-3 py-1 rounded border border-gray-300 bg-gray-100 hover:bg-gray-200
                               dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100 dark:border-gray-600">
                    {{ $filtersOpen ? 'Nascondi' : 'Mostra' }} filtri
                </button>
            </div>
        </div>

        <div id="filters-body" class="{{ $filtersOpen ? '' : 'hidden' }} space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Ricerca globale</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                        class="p-2 border rounded w-full {{ filterActive('q') }}" placeholder="Protocollo, oggetto o richiedente">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">ID</label>
                    <input type="number" name="id" value="{{ request('id') }}"
                        class="p-2 border rounded w-full {{ filterActive('id') }}" min="1">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Numero protocollo</label>
                    <input type="text" name="numero_protocollo" value="{{ request('numero_protocollo') }}"
                        class="p-2 border rounded w-full {{ filterActive('numero_protocollo') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Numero pratica</label>
                    <input type="text" name="numero_pratica" value="{{ request('numero_pratica') }}"
                        class="p-2 border rounded w-full {{ filterActive('numero_pratica') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Anno presentazione</label>
                    <input type="number" name="anno" value="{{ request('anno') }}"
                        class="p-2 border rounded w-full {{ filterActive('anno') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Oggetto</label>
                    <input type="text" name="oggetto" value="{{ request('oggetto') }}"
                        class="p-2 border rounded w-full {{ filterActive('oggetto') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Richiedente</label>
                    <input type="text" name="richiedente" value="{{ request('richiedente') ?? request('cognome') }}"
                        class="p-2 border rounded w-full {{ filterActive('richiedente') ?: filterActive('cognome') }}" placeholder="Nome o cognome">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Tipo pratica</label>
                    <select name="tipo" class="p-2 border rounded w-full {{ filterActive('tipo') }}">
                        <option value="">Tutte</option>
                        @foreach(\App\Models\Pratica::select('sigla_tipo_pratica')->distinct()->pluck('sigla_tipo_pratica') as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo') == $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Via</label>
                    <input type="text" name="via" value="{{ request('via') }}"
                        class="p-2 border rounded w-full {{ filterActive('via') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Civico</label>
                    <input type="text" name="civico" value="{{ request('civico') }}"
                        class="p-2 border rounded w-full {{ filterActive('civico') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Foglio</label>
                    <input type="text" name="foglio" value="{{ request('foglio') }}"
                        class="p-2 border rounded w-full {{ filterActive('foglio') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Particella / Sub</label>
                    <input type="text" name="particella_sub" value="{{ request('particella_sub') }}"
                        class="p-2 border rounded w-full {{ filterActive('particella_sub') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Riferimento libero</label>
                    <input type="text" name="riferimento_libero" value="{{ request('riferimento_libero') }}"
                        class="p-2 border rounded w-full {{ filterActive('riferimento_libero') }}">
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Nota</label>
                    <input type="text" name="nota" value="{{ request('nota') }}"
                        class="p-2 border rounded w-full {{ filterActive('nota') }}">
                </div>

                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data protocollo da</label>
                        <input type="date" name="protocollo_da" value="{{ request('protocollo_da') }}"
                            class="p-2 border rounded w-full {{ filterActive('protocollo_da') }}">
                    </div>
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data protocollo a</label>
                        <input type="date" name="protocollo_a" value="{{ request('protocollo_a') }}"
                            class="p-2 border rounded w-full {{ filterActive('protocollo_a') }}">
                    </div>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data rilascio da</label>
                        <input type="date" name="rilascio_da" value="{{ request('rilascio_da') }}"
                            class="p-2 border rounded w-full {{ filterActive('rilascio_da') }}">
                    </div>
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data rilascio a</label>
                        <input type="date" name="rilascio_a" value="{{ request('rilascio_a') }}"
                            class="p-2 border rounded w-full {{ filterActive('rilascio_a') }}">
                    </div>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Creato da</label>
                        <input type="date" name="created_da" value="{{ request('created_da') }}"
                            class="p-2 border rounded w-full {{ filterActive('created_da') }}">
                    </div>
                    <div>
                        <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Creato a</label>
                        <input type="date" name="created_a" value="{{ request('created_a') }}"
                            class="p-2 border rounded w-full {{ filterActive('created_a') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Testo nei PDF</label>
                    <input type="text" name="pdf" value="{{ request('pdf') }}"
                        class="p-2 border rounded w-full {{ filterActive('pdf') }}">
                </div>

                <label class="flex items-center gap-2 text-sm mt-6 md:mt-0">
                    <input type="checkbox" name="vuote" value="1" @checked(request('vuote') == '1') class="h-4 w-4 {{ request('vuote') == '1' ? 'ring-2 ring-yellow-300 border-yellow-400' : '' }}">
                    <span>Solo pratiche senza file</span>
                </label>

                <label class="flex items-center gap-2 text-sm mt-2 md:mt-0">
                    <input type="checkbox" name="exact" value="1" @checked(request('exact') == '1') class="h-4 w-4 {{ request('exact') == '1' ? 'ring-2 ring-yellow-300 border-yellow-400' : '' }}">
                    <span>Filtri esatti</span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 justify-end pt-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                🔍 Filtra
            </button>
            <a href="{{ route('dashboard') }}"
               onclick="localStorage.removeItem('dashboardFilters'); localStorage.removeItem('dashboardReturn');"
               class="inline-flex items-center gap-2 px-4 py-2 rounded shadow border border-gray-300 bg-gray-100 hover:bg-gray-200 text-gray-900 font-semibold
                      dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white dark:border-gray-500">
                🔄 <span>Reset filtri</span>
            </a>
        </div>
    </form>

    {{-- 📊 Tabella risultati --}}
    <div class="mt-6 bg-white shadow rounded p-4">

@php
    function sortLink($label, $col) {
        $current = request('sort');
        $direction = request('dir', 'asc');

        // Se sto cliccando sulla stessa colonna → cambio direzione
        $newDir = ($current === $col && $direction === 'asc') ? 'desc' : 'asc';

        // Icona direzione
        $icon = '';
        if ($current === $col) {
            $icon = $direction === 'asc' ? '↑' : '↓';
        }

        $params = array_merge(request()->all(), [
            'sort' => $col,
            'dir'  => $newDir
        ]);

        return [
            'url'  => '?' . http_build_query($params),
            'icon' => $icon
        ];
    }
@endphp

<div class="mt-6 bg-white shadow rounded-xl overflow-hidden">

    <table class="w-full text-sm border-collapse">

        <thead class="sticky top-0 bg-gray-100 z-20 shadow">
            <tr class="text-gray-700 text-xs uppercase tracking-wide">

                {{-- ID --}}
                @php $s = sortLink('ID', 'id'); @endphp
                <th class="py-3 px-4 text-left w-16">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        ID {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Numero Pratica --}}
                @php $s = sortLink('N. Pratica', 'numero_pratica'); @endphp
                <th class="py-3 px-4 w-28">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        N. Pratica {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Anno --}}
                @php $s = sortLink('Anno', 'anno_presentazione'); @endphp
                <th class="py-3 px-4 w-20">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        Anno {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Richiedente --}}
                @php $s = sortLink('Richiedente', 'rich_cognome1'); @endphp
                <th class="py-3 px-4 w-48">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        Richiedente {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Oggetto --}}
                @php $s = sortLink('Oggetto', 'oggetto'); @endphp
                <th class="py-3 px-4">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        Oggetto {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Via --}}
                @php $s = sortLink('Via', 'area_circolazione'); @endphp
                <th class="py-3 px-4 w-52">
                    <a href="{{ $s['url'] }}" class="flex items-center gap-1 hover:text-blue-700">
                        Via {!! $s['icon'] !!}
                    </a>
                </th>

                {{-- Files --}}
                @php $s = sortLink('Files', 'files_count'); @endphp
                <th class="py-3 px-4 text-center w-16">
                    <a href="{{ $s['url'] }}" class="flex items-center justify-center gap-1 hover:text-blue-700">
                        📎 {!! $s['icon'] !!}
                    </a>
                </th>

                <th class="py-3 px-4 text-center w-24"></th>
            </tr>
        </thead>

        <tbody>

        @foreach ($pratiche as $p)
            <tr class="border-b last:border-0 odd:bg-gray-50 hover:bg-blue-50 transition">

                <td class="py-2.5 px-4 text-gray-700 font-medium">
                    {{ $p->id }}
                </td>

                <td class="py-2.5 px-4 font-semibold">
                    @php
                        $numeroPraticaOriginal = $p->original_values['numero_pratica'] ?? $p->numero_pratica;
                        $numeroPraticaResolved = $p->resolved['numero_pratica'] ?? $numeroPraticaOriginal;
                    @endphp
                    <span class="text-blue-700 dark:text-blue-300">{{ $numeroPraticaOriginal }}</span>
                    @if($numeroPraticaResolved !== $numeroPraticaOriginal)
                        <span class="text-red-500 dark:text-red-400 text-xs font-normal">({{ $numeroPraticaResolved }})</span>
                    @endif
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    @php
                        $annoOriginal = $p->original_values['anno_presentazione'] ?? $p->anno_presentazione;
                        $annoResolved = $p->resolved['anno_presentazione'] ?? $annoOriginal;
                    @endphp
                    {{ $annoOriginal }}
                    @if($annoResolved !== $annoOriginal)
                        <span class="text-red-500 dark:text-red-400 text-xs font-normal">({{ $annoResolved }})</span>
                    @endif
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    @php
                        $richOriginal = $p->richiedenti_completi;
                        $richResolved = $p->richiedenti_resolti;
                    @endphp
                    {{ $richOriginal ?: '—' }}
                    @if($richResolved && $richResolved !== $richOriginal)
                        <span class="text-red-500 dark:text-red-400 text-xs font-normal">({{ $richResolved }})</span>
                    @endif
                </td>

                <td class="py-2.5 px-4 text-gray-600 text-[13px]">
                    @php
                        $oggettoOriginal = $p->original_values['oggetto'] ?? $p->oggetto;
                        $oggettoResolved = $p->resolved['oggetto'] ?? $oggettoOriginal;
                    @endphp
                    <div class="line-clamp-2 overflow-hidden text-ellipsis" title="{{ $oggettoResolved }}">
                        {{ $oggettoOriginal }}
                        @if($oggettoResolved !== $oggettoOriginal)
                            <span class="text-red-500 dark:text-red-400 text-xs font-normal">({{ $oggettoResolved }})</span>
                        @endif
                    </div>
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    @php
                        $viaOriginal = trim(($p->original_values['area_circolazione'] ?? $p->area_circolazione) . ' ' . ($p->original_values['civico_esponente'] ?? $p->civico_esponente));
                        $viaResolved = trim(($p->resolved['area_circolazione'] ?? $p->area_circolazione) . ' ' . ($p->resolved['civico_esponente'] ?? $p->civico_esponente));
                    @endphp
                    {{ $viaOriginal }}
                    @if($viaResolved !== $viaOriginal)
                        <span class="text-red-500 dark:text-red-400 text-xs font-normal">({{ $viaResolved }})</span>
                    @endif
                </td>

                <td class="py-2.5 px-4 text-center">

                    @if ($p->files_count == 0)
                        <span class="text-red-500 text-xl">●</span>
                    @else
                        <span class="text-green-600 text-xl">●</span>
                    @endif
                </td>

                <td class="py-2.5 px-4 text-center">

                    {{-- Badge PDF trovato --}}
                    @if(request('pdf') && !empty($p->pdf_hits))
                        @php $first = $p->pdf_hits[0] ?? null; @endphp

                        <a href="/pratica/{{ $p->id }}?pdf={{ request('pdf') }}&file={{ urlencode($first) }}"
                            onclick="localStorage.setItem('dashboardReturn', window.location.search)"
                           class="inline-flex items-center gap-1 bg-yellow-300 hover:bg-yellow-400 text-black text-xs px-3 py-1 rounded-full transition shadow"
                           title="Trovato in {{ count($p->pdf_hits) }} documento/i">
                            🔎 {{ count($p->pdf_hits) }}
                        </a>

                        
                    @else

                        <a href="/pratica/{{ $p->id }}"
                        onclick="localStorage.setItem('dashboardReturn', window.location.search)"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded-full transition shadow">
                            Apri →
                        </a>

                    @endif

                </td>

            </tr>
        @endforeach

        </tbody>

    </table>

    <div class="px-4 py-3 bg-gray-50">
        {{ $pratiche->links() }}
    </div>

</div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dashboardForm = document.querySelector('form[method="GET"]');
    const filterToggle = document.getElementById('filters-toggle');
    const filterBody = document.getElementById('filters-body');

    if (!dashboardForm) return;

    // Apertura/chiusura dei filtri
    if (filterToggle && filterBody) {
        filterToggle.addEventListener('click', () => {
            filterBody.classList.toggle('hidden');
            filterToggle.textContent = filterBody.classList.contains('hidden') ? 'Mostra filtri' : 'Nascondi filtri';
        });
    }

    // 🔹 Al submit → salva i filtri nel localStorage
    dashboardForm.addEventListener('submit', () => {
        const filters = Object.fromEntries(new FormData(dashboardForm));
        localStorage.setItem('dashboardFilters', JSON.stringify(filters));
    });

    // 🔹 Se ci sono filtri salvati e NON abbiamo query string → li ripristiniamo
    const saved = localStorage.getItem('dashboardFilters');

    if (saved && Object.keys(Object.fromEntries(new URLSearchParams(window.location.search))).length === 0) {
        const params = JSON.parse(saved);
        const query = new URLSearchParams(params).toString();
        window.location = window.location.pathname + "?" + query;
    }
});
</script>

@endsection
