@extends('layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto">

@php
    $filtersOpen = $activeFilters > 0 && !request()->boolean('hide_filters');

    function filterActive(string $name): string {
        return request()->filled($name) ? 'border-yellow-400 ring-1 ring-yellow-300' : '';
    }

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
</h1>

{{-- TABELLA --}}
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
        {{ $p->pratica_id }}
    </td>

    {{-- Numero pratica --}}
    <td class="py-2.5 px-4">
        <span class="text-blue-700 font-semibold">
            {{ $p->numero_pratica }}
        </span>
    </td>

    {{-- Anno --}}
    <td class="py-2.5 px-4">
        {{ $p->anno_presentazione }}
    </td>

    {{-- Richiedente --}}
    <td class="py-2.5 px-4">
        {{ $p->richiedenti_completi ?: '—' }}
    </td>

    {{-- Oggetto --}}
    <td class="py-2.5 px-4 text-[13px] text-gray-700">
        <div class="line-clamp-2" title="{{ $p->oggetto }}">
            {{ $p->oggetto }}
        </div>
    </td>

    {{-- Via --}}
    <td class="py-2.5 px-4">
        {{ trim($p->area_circolazione . ' ' . $p->civico_esponente) }}
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
            class="text-blue-600 hover:text-blue-800 underline">
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
    {{ $pratiche->links() }}
</div>

</div>
</div>

@endsection
