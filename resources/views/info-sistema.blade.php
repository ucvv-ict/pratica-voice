@extends('layout')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">ℹ️ Info Sistema</h1>
        <a href="{{ url()->previous() }}" class="text-blue-600 underline text-sm">← Torna indietro</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white shadow rounded p-4 border border-gray-200">
            <h2 class="text-lg font-semibold mb-3">Applicazione</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="py-2 text-gray-600">Nome</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $appInfo['name'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Versione</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $appInfo['version'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Commit</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $appInfo['commit'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Modalità</td>
                        <td class="py-2">
                            <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                {{ $appInfo['mode'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Ambiente</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $appInfo['env'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Tenant</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $appInfo['tenant'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white shadow rounded p-4 border border-gray-200">
            <h2 class="text-lg font-semibold mb-3">Sistema</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="py-2 text-gray-600">PHP</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $systemInfo['php'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Laravel</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $systemInfo['laravel'] }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Hostname</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $systemInfo['hostname'] }}</td>
                    </tr>
                    @if($systemInfo['pdf_base_path'])
                    <tr>
                        <td class="py-2 text-gray-600">PDF_BASE_PATH</td>
                        <td class="py-2 font-semibold text-gray-800">{{ $systemInfo['pdf_base_path'] }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white shadow rounded p-4 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Queue</h2>
            @php
                $statusColor = $workerStatus === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200';
                $statusLabel = $workerStatus === 'active' ? 'Attivo (recenti elaborazioni/heartbeat)' : 'Non rilevato / inattivo';
            @endphp
            <span class="px-2 py-1 rounded text-xs font-semibold border {{ $statusColor }}">{{ $statusLabel }}</span>
        </div>

        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="py-2 text-gray-600">Connessione</td>
                    <td class="py-2 font-semibold text-gray-800">{{ $queueConnection }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-gray-600">Heartbeat worker</td>
                    <td class="py-2 font-semibold text-gray-800">
                        @if($heartbeatMinutes === null)
                            <span class="text-gray-500">n/d</span>
                        @else
                            {{ number_format($heartbeatMinutes, 1) }} min fa
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-gray-600">Job in coda</td>
                    <td class="py-2 font-semibold text-gray-800">
                        @if($pendingJobs === null)
                            <span class="text-gray-500">n/d</span>
                        @else
                            {{ $pendingJobs }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-gray-600">Job falliti</td>
                    <td class="py-2 font-semibold text-gray-800">
                        @if($failedJobs === null)
                            <span class="text-gray-500">n/d</span>
                        @else
                            {{ $failedJobs }}
                        @endif
                    </td>
                </tr>
                @if($queueNote)
                <tr>
                    <td class="py-2 text-gray-600">Note</td>
                    <td class="py-2 text-yellow-700">
                        {{ $queueNote }}
                    </td>
                </tr>
                @endif
            </tbody>
        </table>

        <p class="text-sm text-gray-600 mt-3">
            Se il worker non è attivo, i job restano in coda e le operazioni asincrone (es. fascicoli ZIP, indicizzazione PDF) non vengono completate.
        </p>
    </div>

    <div class="bg-white shadow rounded p-4 border border-gray-200">
        <h2 class="text-lg font-semibold mb-3">Ultimi deploy</h2>
        @if(empty($deployHistory))
            <p class="text-sm text-gray-600">Nessun dato disponibile.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2">Data</th>
                        <th class="py-2">Versione</th>
                        <th class="py-2">Commit</th>
                        <th class="py-2">Modalità</th>
                        <th class="py-2">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($deployHistory as $deploy)
                        <tr>
                            <td class="py-2 text-gray-800">{{ \Carbon\Carbon::parse($deploy->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-gray-800">{{ $deploy->version }}</td>
                            <td class="py-2 text-gray-800">{{ $deploy->commit }}</td>
                            <td class="py-2 text-gray-800">{{ $deploy->mode }}</td>
                            <td class="py-2 text-gray-800">{{ $deploy->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
