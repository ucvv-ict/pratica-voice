@extends('layout')

@section('content')
<script src="//unpkg.com/alpinejs" defer></script>

<div class="p-6 max-w-7xl mx-auto">

    <h1 class="text-2xl font-bold mb-6">
        📁 Archivio Pratiche — Dashboard

        @if($activeFilters > 0)
            <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-full">
                {{ $activeFilters }} filtri attivi
            </span>
        @endif
    </h1>

    {{-- 🔍 Barra filtri --}}
    <form method="GET" class="space-y-4 bg-gray-100 p-4 rounded" x-data="{ 
        base: true, 
        pratica: false, 
        rich: false,
        loco: false,
        catasto: false,
        protocollo: false,
        pdfsec: false
    }">

        <!-- =============================== -->
        <!-- 📌 Ricerca Base (sempre visibile) -->
        <!-- =============================== -->
        <div>
            <h3 class="text-md font-bold">
                📌 Ricerca Base
                @if(($activePerSection['base'] ?? 0) > 0)
                    <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $activePerSection['base'] }}
                    </span>
                @endif
            </h3>

            <div class="grid grid-cols-6 gap-4 mt-2">
                <div class="col-span-2">
                    <label class="block text-xs mb-1">Ricerca libera</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div>
                    <label class="block text-xs mb-1">Cognome richiedente</label>
                    <input type="text" name="cognome" value="{{ request('cognome') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div>
                    <label class="block text-xs mb-1">Anno</label>
                    <input type="number" name="anno" value="{{ request('anno') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div>
                    <label class="block text-xs mb-1">Numero pratica</label>
                    <input type="text" name="numero_pratica" value="{{ request('numero_pratica') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>


        <!-- =============================== -->
        <!-- 📂 Dati Pratica -->
        <!-- =============================== -->
        <div class="border p-3 rounded bg-white">
            <button type="button" @click="pratica = !pratica"
                    class="flex justify-between w-full text-left font-semibold">

                <span>
                    📂 Dati Pratica
                    @if(($activePerSection['pratica'] ?? 0) > 0)
                        <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $activePerSection['pratica'] }}
                        </span>
                    @endif
                </span>

                <span x-text="pratica ? '▲' : '▼'"></span>
            </button>

            <div x-show="pratica" class="mt-3 grid grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs mb-1">Tipo pratica</label>
                    <select name="tipo" class="p-2 border rounded w-full">
                        <option value="">Tutte</option>
                        @foreach(\App\Models\Pratica::select('sigla_tipo_pratica')->distinct()->pluck('sigla_tipo_pratica') as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo') == $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs mb-1">Riferimento libero</label>
                    <input type="text" name="riferimento_libero" value="{{ request('riferimento_libero') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div class="col-span-3">
                    <label class="block text-xs mb-1">Nota</label>
                    <input type="text" name="nota" value="{{ request('nota') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>


        <!-- =============================== -->
        <!-- 🏛️ Localizzazione -->
        <!-- =============================== -->
        <div class="border p-3 rounded bg-white">
            <button type="button" @click="loco = !loco"
                    class="flex justify-between w-full text-left font-semibold">

                <span>
                    🏛️ Localizzazione
                    @if(($activePerSection['loco'] ?? 0) > 0)
                        <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $activePerSection['loco'] }}
                        </span>
                    @endif
                </span>

                <span x-text="loco ? '▲' : '▼'"></span>
            </button>

            <div x-show="loco" class="mt-3 grid grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs mb-1">Via</label>
                    <input type="text" name="via" value="{{ request('via') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div>
                    <label class="block text-xs mb-1">Civico</label>
                    <input type="text" name="civico" value="{{ request('civico') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>


        <!-- =============================== -->
        <!-- 🗄️ Dati Catastali -->
        <!-- =============================== -->
        <div class="border p-3 rounded bg-white">
            <button type="button" @click="catasto = !catasto"
                    class="flex justify-between w-full text-left font-semibold">

                <span>
                    🗄️ Dati Catastali
                    @if(($activePerSection['catasto'] ?? 0) > 0)
                        <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $activePerSection['catasto'] }}
                        </span>
                    @endif
                </span>

                <span x-text="catasto ? '▲' : '▼'"></span>
            </button>

            <div x-show="catasto" class="mt-3 grid grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs mb-1">Foglio</label>
                    <input type="text" name="foglio" value="{{ request('foglio') }}"
                        class="p-2 border rounded w-full">
                </div>

                <div>
                    <label class="block text-xs mb-1">Particella / Sub</label>
                    <input type="text" name="particella_sub" value="{{ request('particella_sub') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>


        <!-- =============================== -->
        <!-- 🗂️ Protocollo & Rilascio -->
        <!-- =============================== -->
        <div class="border p-3 rounded bg-white">
            <button type="button" @click="protocollo = !protocollo"
                    class="flex justify-between w-full text-left font-semibold">

                <span>
                    📑 Protocollo & Rilascio
                    @if(($activePerSection['protocollo'] ?? 0) > 0)
                        <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $activePerSection['protocollo'] }}
                        </span>
                    @endif
                </span>

                <span x-text="protocollo ? '▲' : '▼'"></span>
            </button>

            <div x-show="protocollo" class="mt-3 grid grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs mb-1">Protocollo da</label>
                    <input type="date" name="protocollo_da" value="{{ request('protocollo_da') }}"
                        class="p-2 border rounded w-full">
                </div>
                <div>
                    <label class="block text-xs mb-1">Protocollo a</label>
                    <input type="date" name="protocollo_a" value="{{ request('protocollo_a') }}"
                        class="p-2 border rounded w-full">
                </div>
                <div>
                    <label class="block text-xs mb-1">Rilascio da</label>
                    <input type="date" name="rilascio_da" value="{{ request('rilascio_da') }}"
                        class="p-2 border rounded w-full">
                </div>
                <div>
                    <label class="block text-xs mb-1">Rilascio a</label>
                    <input type="date" name="rilascio_a" value="{{ request('rilascio_a') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>

        <!-- =============================== -->
        <!-- 📄 Ricerca nei PDF -->
        <!-- =============================== -->
        <div class="border p-3 rounded bg-white">
            <button type="button" @click="pdfsec = !pdfsec"
                    class="flex justify-between w-full text-left font-semibold">

                <span>
                    🔎 Ricerca nei PDF
                    @if(($activePerSection['pdfsec'] ?? 0) > 0)
                        <span class="ml-2 bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $activePerSection['pdfsec'] }}
                        </span>
                    @endif
                </span>

                <span x-text="pdfsec ? '▲' : '▼'"></span>
            </button>

            <div x-show="pdfsec" class="mt-3 grid grid-cols-6 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs mb-1">Testo da cercare nei PDF</label>
                    <input type="text" name="pdf" value="{{ request('pdf') }}"
                        class="p-2 border rounded w-full">
                </div>
            </div>
        </div>

        <!-- =============================== -->
        <!-- BOTTONI -->
        <!-- =============================== -->
        <div>
            <button class="bg-blue-600 text-white px-4 py-2 rounded w-full">
                🔍 Filtra
            </button>
        </div>

    </form>
    
    

    <div class="mt-3">
        <a href="#" 
        onclick="resetDashboardFilters()"
        class="inline-block bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded shadow">
            🔄 Reset filtri
        </a>
    </div>

    <script>
    function resetDashboardFilters() {
        // 🔥 Cancella filtri salvati
        localStorage.removeItem('dashboardFilters');
        localStorage.removeItem('dashboardReturn');

        // 🔥 Vai alla dashboard pulita
        window.location = '/dashboard';
    }
    </script>

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

                <td class="py-2.5 px-4 text-gray-900 font-semibold">
                    {{ $p->numero_pratica }}
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    {{ $p->anno_presentazione }}
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    {{ $p->richiedenti_completi }}
                </td>

                <td class="py-2.5 px-4 text-gray-600 text-[13px]">
                    <div class="line-clamp-2 overflow-hidden text-ellipsis" title="{{ $p->oggetto }}">
                        {{ $p->oggetto }}
                    </div>
                </td>

                <td class="py-2.5 px-4 text-gray-700">
                    {{ $p->area_circolazione }} {{ $p->civico_esponente }}
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

    if (!dashboardForm) return;

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
