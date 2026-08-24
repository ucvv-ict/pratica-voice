@extends('layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">📊 Statistiche utilizzo — {{ $tenantName }}</h1>
            <p class="mt-1 text-sm text-gray-600">Dati basati esclusivamente sulle generazioni dei fascicoli.</p>
            <p class="mt-2 text-sm font-semibold text-gray-700">Periodo: {{ $filters['period_label'] }}</p>
        </div>
        <a href="{{ route('statistiche.csv', request()->only(['from', 'to', 'group', 'preset'])) }}"
           class="inline-flex items-center rounded bg-blue-600 px-4 py-2 font-semibold text-white shadow hover:bg-blue-700">
            Esporta CSV
        </a>
    </div>

    <form method="GET" action="{{ route('statistiche.index') }}" class="bg-white shadow rounded-xl border border-gray-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
            <div>
                <label for="preset" class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Periodo</label>
                <select id="preset" name="preset" class="p-2 border rounded w-full">
                    <option value="30_days" @selected($filters['preset'] === '30_days')>Ultimi 30 giorni</option>
                    <option value="3_months" @selected($filters['preset'] === '3_months')>Ultimi 3 mesi</option>
                    <option value="6_months" @selected($filters['preset'] === '6_months')>Ultimi 6 mesi</option>
                    <option value="current_year" @selected($filters['preset'] === 'current_year')>Anno corrente</option>
                    <option value="all" @selected($filters['preset'] === 'all')>Tutto</option>
                    <option value="custom" @selected($filters['preset'] === 'custom')>Personalizzato</option>
                </select>
            </div>
            <div>
                <label for="from" class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data da</label>
                <input id="from" name="from" type="date" value="{{ $filters['input_from'] }}" class="p-2 border rounded w-full">
            </div>
            <div>
                <label for="to" class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Data a</label>
                <input id="to" name="to" type="date" value="{{ $filters['input_to'] }}" class="p-2 border rounded w-full">
            </div>
            <div>
                <label for="group" class="block text-xs mb-1 uppercase tracking-wide text-gray-600">Granularità</label>
                <select id="group" name="group" class="p-2 border rounded w-full">
                    <option value="day" @selected($filters['group'] === 'day')>Giorno</option>
                    <option value="week" @selected($filters['group'] === 'week')>Settimana</option>
                    <option value="month" @selected($filters['group'] === 'month')>Mese</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">Applica</button>
                <a href="{{ route('statistiche.index') }}" class="px-4 py-2 rounded shadow border bg-gray-100 whitespace-nowrap">Azzera filtri</a>
            </div>
        </div>
        @error('from')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('to')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </form>

    @php
        $cards = [
            ['Fascicoli completati', (int) ($riepilogo->fascicoli_completati ?? 0)],
            ['Pratiche distinte', (int) ($riepilogo->pratiche_distinte ?? 0)],
            ['Errori', (int) ($riepilogo->errori ?? 0)],
            ['Totale generazioni', (int) ($riepilogo->totale_generazioni ?? 0)],
            ['Primo fascicolo generato', $riepilogo->primo_fascicolo ? \Carbon\Carbon::parse($riepilogo->primo_fascicolo)->format('d/m/Y H:i') : '—'],
            ['Ultimo fascicolo generato', $riepilogo->ultimo_fascicolo ? \Carbon\Carbon::parse($riepilogo->ultimo_fascicolo)->format('d/m/Y H:i') : '—'],
        ];
        $groupLabels = ['day' => 'Giorno', 'week' => 'Settimana', 'month' => 'Mese'];
        $groupLabel = $groupLabels[$filters['group']];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($cards as [$label, $value])
            <div class="bg-white shadow rounded-xl border border-gray-200 p-5">
                <p class="text-sm text-gray-600">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-800">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if($statistiche->isEmpty())
        <div class="bg-white shadow rounded-xl border border-gray-200 p-8 text-center">
            <h2 class="text-lg font-semibold">Nessuna generazione disponibile</h2>
            <p class="mt-2 text-sm text-gray-600">Non risultano tentativi di generazione nel periodo selezionato.</p>
        </div>
    @else
        @php
            $chartWidth = 900;
            $chartHeight = 300;
            $paddingX = 48;
            $paddingY = 30;
            $plotWidth = $chartWidth - ($paddingX * 2);
            $plotHeight = $chartHeight - ($paddingY * 2);
            $maxValue = max(1, (int) $statistiche->max(fn ($row) => max($row->fascicoli_completati, $row->pratiche_distinte)));
            $pointX = fn ($index) => $paddingX + ($statistiche->count() === 1 ? $plotWidth / 2 : ($index * $plotWidth / ($statistiche->count() - 1)));
            $pointY = fn ($value) => $paddingY + $plotHeight - (((int) $value / $maxValue) * $plotHeight);
            $completedPoints = $statistiche->values()->map(fn ($row, $index) => $pointX($index).','.$pointY($row->fascicoli_completati))->implode(' ');
            $pratichePoints = $statistiche->values()->map(fn ($row, $index) => $pointX($index).','.$pointY($row->pratiche_distinte))->implode(' ');
            $labelStep = max(1, (int) ceil($statistiche->count() / 10));
            $hitWidth = max(12, $plotWidth / max(1, $statistiche->count()));
        @endphp

        <div class="bg-white shadow rounded-xl border border-gray-200 p-5 relative">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-semibold">Andamento per {{ strtolower($groupLabel) }}</h2>
                <div class="flex gap-4 text-xs text-gray-600">
                    <span><span class="inline-block w-3 h-3 rounded-full bg-blue-600 mr-1"></span>Fascicoli completati</span>
                    <span><span class="inline-block w-3 h-3 rounded-full bg-emerald-500 mr-1"></span>Pratiche distinte</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <svg id="statistics-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full min-w-[700px]" role="img" aria-label="Grafico dell'andamento dei fascicoli">
                    <line x1="{{ $paddingX }}" y1="{{ $paddingY }}" x2="{{ $paddingX }}" y2="{{ $chartHeight - $paddingY }}" stroke="currentColor" class="text-gray-500" />
                    <line x1="{{ $paddingX }}" y1="{{ $chartHeight - $paddingY }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $chartHeight - $paddingY }}" stroke="currentColor" class="text-gray-500" />
                    <text x="{{ $paddingX - 8 }}" y="{{ $paddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">{{ $maxValue }}</text>
                    <text x="{{ $paddingX - 8 }}" y="{{ $chartHeight - $paddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">0</text>
                    <polyline points="{{ $completedPoints }}" fill="none" stroke="#2563eb" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
                    <polyline points="{{ $pratichePoints }}" fill="none" stroke="#10b981" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach($statistiche as $index => $row)
                        <circle cx="{{ $pointX($index) }}" cy="{{ $pointY($row->fascicoli_completati) }}" r="4" fill="#2563eb" />
                        <circle cx="{{ $pointX($index) }}" cy="{{ $pointY($row->pratiche_distinte) }}" r="4" fill="#10b981" />
                        <rect class="chart-hit-point cursor-pointer" x="{{ $pointX($index) - ($hitWidth / 2) }}" y="{{ $paddingY }}" width="{{ $hitWidth }}" height="{{ $plotHeight }}" fill="transparent"
                                data-label="{{ $row->label }}"
                                data-completed="{{ $row->fascicoli_completati }}"
                                data-pratiche="{{ $row->pratiche_distinte }}"
                                data-errori="{{ $row->errori }}"
                                data-totale="{{ $row->totale_generazioni }}"
                                data-url="{{ $row->drill_url }}" />
                        @if($index % $labelStep === 0 || $loop->last)
                            <text x="{{ $pointX($index) }}" y="{{ $chartHeight - 8 }}" text-anchor="middle" fill="currentColor" class="text-xs text-gray-600">{{ $row->label }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
            <div id="chart-tooltip" class="hidden fixed z-50 pointer-events-none rounded-lg bg-gray-900 text-white text-xs shadow-lg p-3 leading-5"></div>
            @if($filters['group'] === 'month')
                <p class="mt-2 text-xs text-gray-600">Clicca un punto o una riga per visualizzare il dettaglio giornaliero del mese.</p>
            @endif
        </div>

        <div class="bg-white shadow rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-200"><h2 class="text-lg font-semibold">Statistiche per {{ strtolower($groupLabel) }}</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-700">
                        <tr>
                            <th class="py-3 px-4">{{ $groupLabel }}</th>
                            <th class="py-3 px-4 text-right">Fascicoli completati</th>
                            <th class="py-3 px-4 text-right">Pratiche distinte</th>
                            <th class="py-3 px-4 text-right">Errori</th>
                            <th class="py-3 px-4 text-right">Totale generazioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($statistiche as $row)
                            <tr class="hover:bg-blue-50 {{ $row->drill_url ? 'cursor-pointer' : '' }}" @if($row->drill_url) onclick='window.location.href = @js($row->drill_url)' tabindex="0" onkeydown="if(event.key === 'Enter') this.click()" @endif>
                                <td class="py-3 px-4 font-medium">{{ $row->label }}</td>
                                <td class="py-3 px-4 text-right">{{ $row->fascicoli_completati }}</td>
                                <td class="py-3 px-4 text-right">{{ $row->pratiche_distinte }}</td>
                                <td class="py-3 px-4 text-right">{{ $row->errori }}</td>
                                <td class="py-3 px-4 text-right">{{ $row->totale_generazioni }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    const preset = document.getElementById('preset');
    const from = document.getElementById('from');
    const to = document.getElementById('to');
    [from, to].forEach(input => input?.addEventListener('input', () => { preset.value = 'custom'; }));
    preset?.addEventListener('change', () => {
        if (preset.value !== 'custom') {
            from.value = '';
            to.value = '';
        }
    });

    const tooltip = document.getElementById('chart-tooltip');
    document.querySelectorAll('.chart-hit-point').forEach(point => {
        point.addEventListener('mouseenter', event => {
            tooltip.innerHTML = `<strong>${point.dataset.label}</strong><br>` +
                `Fascicoli completati: ${point.dataset.completed}<br>` +
                `Pratiche distinte: ${point.dataset.pratiche}<br>` +
                `Errori: ${point.dataset.errori}<br>` +
                `Totale generazioni: ${point.dataset.totale}`;
            tooltip.classList.remove('hidden');
            moveTooltip(event);
        });
        point.addEventListener('mousemove', moveTooltip);
        point.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
        point.addEventListener('click', () => {
            if (point.dataset.url) window.location.href = point.dataset.url;
        });
    });

    function moveTooltip(event) {
        if (!tooltip) return;
        tooltip.style.left = `${Math.min(event.clientX + 14, window.innerWidth - tooltip.offsetWidth - 10)}px`;
        tooltip.style.top = `${Math.max(10, event.clientY - tooltip.offsetHeight - 14)}px`;
    }
})();
</script>
@endsection
