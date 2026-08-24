@extends('layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">📊 Statistiche utilizzo — {{ $tenantName }}</h1>
            <p class="mt-1 text-sm text-gray-600">Dati basati esclusivamente sulle generazioni dei fascicoli.</p>
        </div>
        <a href="{{ route('statistiche.csv') }}"
           class="inline-flex items-center rounded bg-blue-600 px-4 py-2 font-semibold text-white shadow hover:bg-blue-700">
            Esporta CSV
        </a>
    </div>

    @php
        $cards = [
            ['Fascicoli completati totali', (int) ($riepilogo->fascicoli_completati ?? 0)],
            ['Pratiche distinte', (int) ($riepilogo->pratiche_distinte ?? 0)],
            ['Generazioni con errore', (int) ($riepilogo->errori ?? 0)],
            ['Totale tentativi di generazione', (int) ($riepilogo->totale_generazioni ?? 0)],
            ['Primo fascicolo generato', $riepilogo->primo_fascicolo ? \Carbon\Carbon::parse($riepilogo->primo_fascicolo)->format('d/m/Y H:i') : '—'],
            ['Ultimo fascicolo generato', $riepilogo->ultimo_fascicolo ? \Carbon\Carbon::parse($riepilogo->ultimo_fascicolo)->format('d/m/Y H:i') : '—'],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($cards as [$label, $value])
            <div class="bg-white shadow rounded-xl border border-gray-200 p-5">
                <p class="text-sm text-gray-600">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-800">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if($mensili->isEmpty())
        <div class="bg-white shadow rounded-xl border border-gray-200 p-8 text-center">
            <h2 class="text-lg font-semibold">Nessuna generazione disponibile</h2>
            <p class="mt-2 text-sm text-gray-600">Le statistiche mensili compariranno dopo il primo tentativo di generazione.</p>
        </div>
    @else
        @php
            $chartWidth = 900;
            $chartHeight = 280;
            $paddingX = 45;
            $paddingY = 30;
            $plotWidth = $chartWidth - ($paddingX * 2);
            $plotHeight = $chartHeight - ($paddingY * 2);
            $maxValue = max(1, (int) $mensili->max(fn ($row) => max($row->fascicoli_completati, $row->pratiche_distinte)));
            $pointX = fn ($index) => $paddingX + ($mensili->count() === 1 ? $plotWidth / 2 : ($index * $plotWidth / ($mensili->count() - 1)));
            $pointY = fn ($value) => $paddingY + $plotHeight - (((int) $value / $maxValue) * $plotHeight);
            $completedPoints = $mensili->values()->map(fn ($row, $index) => $pointX($index).','.$pointY($row->fascicoli_completati))->implode(' ');
            $pratichePoints = $mensili->values()->map(fn ($row, $index) => $pointX($index).','.$pointY($row->pratiche_distinte))->implode(' ');
        @endphp

        <div class="bg-white shadow rounded-xl border border-gray-200 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-semibold">Andamento mensile</h2>
                <div class="flex gap-4 text-xs text-gray-600">
                    <span><span class="inline-block w-3 h-3 rounded-full bg-blue-600 mr-1"></span>Fascicoli completati</span>
                    <span><span class="inline-block w-3 h-3 rounded-full bg-emerald-500 mr-1"></span>Pratiche distinte</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full min-w-[700px]" role="img" aria-label="Grafico dell'andamento mensile dei fascicoli">
                    <line x1="{{ $paddingX }}" y1="{{ $paddingY }}" x2="{{ $paddingX }}" y2="{{ $chartHeight - $paddingY }}" stroke="currentColor" class="text-gray-500" />
                    <line x1="{{ $paddingX }}" y1="{{ $chartHeight - $paddingY }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $chartHeight - $paddingY }}" stroke="currentColor" class="text-gray-500" />
                    <text x="{{ $paddingX - 8 }}" y="{{ $paddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">{{ $maxValue }}</text>
                    <text x="{{ $paddingX - 8 }}" y="{{ $chartHeight - $paddingY + 4 }}" text-anchor="end" fill="currentColor" class="text-xs text-gray-600">0</text>
                    <polyline points="{{ $completedPoints }}" fill="none" stroke="#2563eb" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
                    <polyline points="{{ $pratichePoints }}" fill="none" stroke="#10b981" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach($mensili as $index => $row)
                        <circle cx="{{ $pointX($index) }}" cy="{{ $pointY($row->fascicoli_completati) }}" r="4" fill="#2563eb"><title>{{ $row->mese }}: {{ $row->fascicoli_completati }} completati</title></circle>
                        <circle cx="{{ $pointX($index) }}" cy="{{ $pointY($row->pratiche_distinte) }}" r="4" fill="#10b981"><title>{{ $row->mese }}: {{ $row->pratiche_distinte }} pratiche</title></circle>
                        <text x="{{ $pointX($index) }}" y="{{ $chartHeight - 8 }}" text-anchor="middle" fill="currentColor" class="text-xs text-gray-600">{{ $row->mese }}</text>
                    @endforeach
                </svg>
            </div>
        </div>

        <div class="bg-white shadow rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Statistiche mensili</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-700">
                        <tr>
                            <th class="py-3 px-4">Mese</th>
                            <th class="py-3 px-4 text-right">Fascicoli completati</th>
                            <th class="py-3 px-4 text-right">Pratiche distinte</th>
                            <th class="py-3 px-4 text-right">Errori</th>
                            <th class="py-3 px-4 text-right">Totale generazioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($mensili as $row)
                            <tr class="hover:bg-blue-50">
                                <td class="py-3 px-4 font-medium">{{ $row->mese }}</td>
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
@endsection
