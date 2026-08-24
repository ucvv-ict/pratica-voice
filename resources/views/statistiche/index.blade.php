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

    @php
        $heatColors = [
            0 => 'bg-gray-100 border-gray-200',
            1 => 'bg-emerald-200 border-emerald-300',
            2 => 'bg-emerald-400 border-emerald-500',
            3 => 'bg-emerald-600 border-emerald-700',
            4 => 'bg-emerald-800 border-emerald-900',
        ];
    @endphp
    <section class="bg-white shadow rounded-xl border border-gray-200 p-5 relative" aria-labelledby="heatmap-title">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h2 id="heatmap-title" class="text-lg font-semibold">Attività giornaliera — ultimi 12 mesi</h2>
                <p class="text-xs text-gray-600 mt-1">Dal {{ $heatmap['from']->format('d/m/Y') }} al {{ $heatmap['to']->format('d/m/Y') }}, indipendentemente dai filtri.</p>
            </div>
            <div class="flex items-center gap-1 text-xs text-gray-600" aria-label="Scala intensità">
                <span class="mr-1">Meno</span>
                @foreach($heatColors as $color)
                    <span class="block w-3 h-3 rounded-sm border {{ $color }}"></span>
                @endforeach
                <span class="ml-1">Più</span>
            </div>
        </div>
        <div class="overflow-x-auto pb-2">
            <div class="min-w-max">
                <div class="ml-9 grid gap-[3px] h-5 text-[10px] text-gray-600" style="grid-template-columns: repeat({{ $heatmap['weeks'] }}, 12px)">
                    @foreach($heatmap['months'] as $month)
                        <span class="whitespace-nowrap" style="grid-column: {{ $month->week + 1 }} / span 4">{{ $month->label }}</span>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <div class="grid grid-rows-7 gap-[3px] w-7 text-[10px] leading-3 text-gray-600" aria-hidden="true">
                        <span>Lun</span><span></span><span>Mer</span><span></span><span>Ven</span><span></span><span>Dom</span>
                    </div>
                    <div class="grid grid-rows-7 grid-flow-col gap-[3px]" style="grid-template-columns: repeat({{ $heatmap['weeks'] }}, 12px)">
                        @foreach($heatmap['days'] as $day)
                            @if($day->in_range)
                                <button type="button"
                                        class="heatmap-day block w-3 h-3 rounded-sm border {{ $heatColors[$day->level] }} {{ $day->weekend && $day->level === 0 ? 'ring-1 ring-inset ring-gray-300' : '' }}"
                                        aria-label="{{ $day->label }}: {{ $day->completed }} fascicoli completati, {{ $day->pratiche_distinte }} pratiche distinte"
                                        data-label="{{ $day->label }}"
                                        data-completed="{{ $day->completed }}"
                                        data-pratiche="{{ $day->pratiche_distinte }}"></button>
                            @else
                                <span class="block w-3 h-3" aria-hidden="true"></span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div id="heatmap-tooltip" class="hidden fixed z-50 pointer-events-none rounded-lg bg-gray-900 text-white text-xs shadow-lg p-3 leading-5"></div>
    </section>

    <section class="bg-white shadow rounded-xl border border-gray-200 p-5 relative" aria-labelledby="cumulative-title">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 id="cumulative-title" class="text-lg font-semibold">Fascicoli completati cumulativi</h2>
                <p class="text-xs text-gray-600 mt-1">Totale progressivo mensile dal primo fascicolo completato al mese corrente; non influenzato dai filtri.</p>
            </div>
            <span class="text-xs text-gray-600"><span class="inline-block w-3 h-3 rounded-full bg-violet-600 mr-1"></span>Fascicoli completati cumulativi</span>
        </div>
        @if($cumulativo->isEmpty())
            <p class="py-8 text-center text-sm text-gray-600">Nessun fascicolo completato disponibile.</p>
        @else
            @php
                $cumulativeWidth = 900;
                $cumulativeHeight = 280;
                $cumulativePaddingX = 48;
                $cumulativePaddingY = 30;
                $cumulativePlotWidth = $cumulativeWidth - ($cumulativePaddingX * 2);
                $cumulativePlotHeight = $cumulativeHeight - ($cumulativePaddingY * 2);
                $cumulativeMax = max(1, (int) $cumulativo->max('totale'));
                $cumulativeX = fn ($index) => $cumulativePaddingX + ($cumulativo->count() === 1 ? $cumulativePlotWidth / 2 : ($index * $cumulativePlotWidth / ($cumulativo->count() - 1)));
                $cumulativeY = fn ($value) => $cumulativePaddingY + $cumulativePlotHeight - (((int) $value / $cumulativeMax) * $cumulativePlotHeight);
                $cumulativePoints = $cumulativo->values()->map(fn ($row, $index) => $cumulativeX($index).','.$cumulativeY($row->totale))->implode(' ');
                $cumulativeLabelStep = max(1, (int) ceil($cumulativo->count() / 10));
                $cumulativeHitWidth = max(12, $cumulativePlotWidth / max(1, $cumulativo->count()));
            @endphp
            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $cumulativeWidth }} {{ $cumulativeHeight }}" class="w-full min-w-[700px]" role="img" aria-label="Grafico cumulativo dei fascicoli completati">
                    <line x1="{{ $cumulativePaddingX }}" y1="{{ $cumulativePaddingY }}" x2="{{ $cumulativePaddingX }}" y2="{{ $cumulativeHeight - $cumulativePaddingY }}" stroke="currentColor" class="text-gray-500" />
                    <line x1="{{ $cumulativePaddingX }}" y1="{{ $cumulativeHeight - $cumulativePaddingY }}" x2="{{ $cumulativeWidth - $cumulativePaddingX }}" y2="{{ $cumulativeHeight - $cumulativePaddingY }}" stroke="currentColor" class="text-gray-500" />
                    <text x="{{ $cumulativePaddingX - 8 }}" y="{{ $cumulativePaddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">{{ $cumulativeMax }}</text>
                    <text x="{{ $cumulativePaddingX - 8 }}" y="{{ $cumulativeHeight - $cumulativePaddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">0</text>
                    <polyline points="{{ $cumulativePoints }}" fill="none" stroke="#7c3aed" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach($cumulativo as $index => $row)
                        <circle cx="{{ $cumulativeX($index) }}" cy="{{ $cumulativeY($row->totale) }}" r="4" fill="#7c3aed" />
                        <rect class="cumulative-hit-point" x="{{ $cumulativeX($index) - ($cumulativeHitWidth / 2) }}" y="{{ $cumulativePaddingY }}" width="{{ $cumulativeHitWidth }}" height="{{ $cumulativePlotHeight }}" fill="transparent" data-label="{{ $row->label }}" data-totale="{{ $row->totale }}" />
                        @if($index % $cumulativeLabelStep === 0 || $loop->last)
                            <text x="{{ $cumulativeX($index) }}" y="{{ $cumulativeHeight - 8 }}" text-anchor="middle" fill="currentColor" class="text-xs text-gray-600">{{ $row->periodo }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
            <div id="cumulative-tooltip" class="hidden fixed z-50 pointer-events-none rounded-lg bg-gray-900 text-white text-xs shadow-lg p-3 leading-5"></div>
        @endif
    </section>

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
            <div id="statistics-chart-scroll" class="overflow-x-auto">
                <svg id="statistics-chart" data-group="{{ $filters['group'] }}" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full min-w-[700px]" role="img" aria-label="Grafico dell'andamento dei fascicoli">
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
                                data-label="{{ $row->tooltip_label }}"
                                data-completed="{{ $row->fascicoli_completati }}"
                                data-pratiche="{{ $row->pratiche_distinte }}"
                                data-errori="{{ $row->errori }}"
                                data-totale="{{ $row->totale_generazioni }}"
                                data-url="{{ $row->drill_url }}" />
                        <text x="{{ $pointX($index) }}" y="{{ $chartHeight - 8 }}" text-anchor="middle" fill="currentColor"
                              class="x-axis-tick text-gray-600" data-index="{{ $index }}" style="display:none;font-size:11px">{{ $row->axis_label }}</text>
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

    <section class="border-t-4 border-slate-400 pt-6 space-y-5" aria-labelledby="maintenance-title">
        <div>
            <h2 id="maintenance-title" class="text-xl font-bold">🛠️ Manutenzione tecnica</h2>
            <p class="mt-1 text-sm text-gray-600">I dati di deploy sono indicativi e derivano dalle registrazioni automatiche disponibili sul sistema.</p>
            @if($filters['from'] || $filters['to'])
                <p class="mt-1 text-xs font-semibold text-gray-600">Periodo applicato: {{ $filters['period_label'] }}</p>
            @endif
        </div>

        @if(! $manutenzione['available'])
            <div class="bg-white shadow rounded-xl border border-gray-200 p-8 text-center">
                <h3 class="font-semibold">Dati di manutenzione non disponibili</h3>
                <p class="mt-2 text-sm text-gray-600">La tabella di audit dei deploy non è presente in questa installazione.</p>
            </div>
        @else
            @php
                $ultimoDeploy = $manutenzione['ultimo_deploy'];
                $maintenanceCards = [
                    ['Ultimo deploy registrato', $ultimoDeploy ? \Carbon\Carbon::parse($ultimoDeploy->created_at)->format('d/m/Y H:i') : '—', $ultimoDeploy?->commit ? 'Commit '.$ultimoDeploy->commit : null],
                    ['Deploy registrati negli ultimi 30 giorni', $manutenzione['ultimi_30_giorni'], null],
                    ['Giorni con deploy negli ultimi 90 giorni', $manutenzione['giorni_ultimi_90'], null],
                    ['Commit distinti deployati negli ultimi 90 giorni', $manutenzione['commit_ultimi_90'], null],
                    ['Totale commit distinti nello storico', $manutenzione['commit_storici'], 'Non influenzato dal filtro periodo'],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach($maintenanceCards as [$label, $value, $detail])
                    <div class="bg-white shadow rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-600">{{ $label }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-800">{{ $value }}</p>
                        @if($detail)<p class="mt-1 text-xs text-gray-500">{{ $detail }}</p>@endif
                    </div>
                @endforeach
            </div>

            @if(! $manutenzione['has_data'])
                <div class="bg-white shadow rounded-xl border border-gray-200 p-8 text-center">
                    <h3 class="font-semibold">Nessun deploy automatico registrato</h3>
                    <p class="mt-2 text-sm text-gray-600">Non risultano registrazioni create da deploy.sh nel periodo selezionato.</p>
                </div>
            @else
                <div class="bg-white shadow rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Deploy automatici per mese</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-700">
                                <tr>
                                    <th class="py-3 px-4">Mese</th>
                                    <th class="py-3 px-4 text-right">Registrazioni deploy</th>
                                    <th class="py-3 px-4 text-right">Commit distinti</th>
                                    <th class="py-3 px-4 text-right">Giorni con deploy</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($manutenzione['mensili'] as $row)
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-3 px-4 font-medium">{{ $row->mese }}</td>
                                        <td class="py-3 px-4 text-right">{{ $row->registrazioni_deploy }}</td>
                                        <td class="py-3 px-4 text-right">{{ $row->commit_distinti }}</td>
                                        <td class="py-3 px-4 text-right">{{ $row->giorni_con_deploy }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </section>
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

    const chart = document.getElementById('statistics-chart');
    const chartScroll = document.getElementById('statistics-chart-scroll');
    let resizeFrame;

    function updateXAxisTicks() {
        if (!chart || !chartScroll) return;

        const ticks = Array.from(chart.querySelectorAll('.x-axis-tick'));
        const count = ticks.length;
        if (!count) return;

        const width = chartScroll.clientWidth;
        const mobile = width < 640;
        const group = chart.dataset.group;
        let maxTicks;

        if (group === 'day') {
            maxTicks = count <= 14 ? count : (mobile ? 5 : (count <= 31 ? 10 : 12));
        } else if (group === 'week') {
            maxTicks = count <= 12 ? (mobile ? Math.min(6, count) : count) : (mobile ? 5 : 10);
        } else {
            maxTicks = count <= 12 ? (mobile ? Math.min(6, count) : count) : (mobile ? 6 : 12);
        }

        const step = maxTicks <= 1 ? count : Math.max(1, Math.ceil((count - 1) / (maxTicks - 1)));
        const visible = ticks.filter((tick, index) => index === 0 || index === count - 1 || index % step === 0);
        const spacing = visible.length > 1 ? width / (visible.length - 1) : width;
        const labelWidth = group === 'week' ? 68 : (group === 'day' ? 42 : 52);
        const rotate = spacing < labelWidth + 6;

        ticks.forEach((tick, index) => {
            const show = index === 0 || index === count - 1 || index % step === 0;
            tick.style.display = show ? '' : 'none';
            tick.style.fontSize = count > maxTicks ? '10px' : '11px';
            tick.setAttribute('text-anchor', rotate ? 'end' : 'middle');
            tick.setAttribute('transform', rotate ? `rotate(-30 ${tick.getAttribute('x')} ${tick.getAttribute('y')})` : '');
        });
    }

    updateXAxisTicks();
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeFrame);
        resizeFrame = requestAnimationFrame(updateXAxisTicks);
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

    attachTooltip('.heatmap-day', 'heatmap-tooltip', point =>
        `<strong>${point.dataset.label}</strong><br>` +
        `Fascicoli completati: ${point.dataset.completed}<br>` +
        `Pratiche distinte: ${point.dataset.pratiche}`
    );
    attachTooltip('.cumulative-hit-point', 'cumulative-tooltip', point =>
        `<strong>${point.dataset.label}</strong><br>` +
        `Totale cumulativo: ${point.dataset.totale}`
    );

    function attachTooltip(selector, tooltipId, content) {
        const element = document.getElementById(tooltipId);
        if (!element) return;

        document.querySelectorAll(selector).forEach(point => {
            point.addEventListener('mouseenter', event => {
                element.innerHTML = content(point);
                element.classList.remove('hidden');
                positionTooltip(element, event);
            });
            point.addEventListener('mousemove', event => positionTooltip(element, event));
            point.addEventListener('mouseleave', () => element.classList.add('hidden'));
            point.addEventListener('focus', () => {
                element.innerHTML = content(point);
                element.classList.remove('hidden');
                const bounds = point.getBoundingClientRect();
                positionTooltip(element, { clientX: bounds.left, clientY: bounds.top });
            });
            point.addEventListener('blur', () => element.classList.add('hidden'));
        });
    }

    function positionTooltip(element, event) {
        element.style.left = `${Math.min(event.clientX + 14, window.innerWidth - element.offsetWidth - 10)}px`;
        element.style.top = `${Math.max(10, event.clientY - element.offsetHeight - 14)}px`;
    }
})();
</script>
@endsection
