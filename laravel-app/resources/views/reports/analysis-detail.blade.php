@extends('layouts.app')
@section('title', 'Detail Analisis')
@section('content')
<a href="{{ route('cases.show', $case) }}" class="text-sm text-navy hover:underline">&larr; Kembali ke {{ $case->case_number }}</a>

<h1 class="text-2xl font-bold text-navy mt-2 mb-1 capitalize">
    Hasil Analisis: {{ str_replace('_', ' ', $analysis->analysis_type) }}
</h1>
<p class="text-slate-500 text-sm mb-6">Dijalankan oleh {{ $analysis->requester->name ?? '-' }} pada {{ $analysis->created_at->format('d M Y H:i') }}</p>

@php $result = $analysis->result; @endphp

<div class="bg-white rounded-lg shadow p-5 mb-6">
    <h2 class="font-semibold mb-2">Ringkasan</h2>
    <p class="text-sm">{{ $result['summary'] ?? '-' }}</p>
</div>

@if ($analysis->analysis_type === 'anomaly')
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-3">Daftar Anomali ({{ $result['anomaly_count'] ?? 0 }} dari {{ $result['total_records'] ?? 0 }} record)</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b"><tr><th class="py-2">#</th><th>Skor</th><th>Alasan</th></tr></thead>
            <tbody>
            @forelse (($result['anomalies'] ?? []) as $a)
                <tr class="border-b">
                    <td class="py-2">{{ $a['row_index'] }}</td>
                    <td>{{ $a['anomaly_score'] ?? '-' }}</td>
                    <td>{{ implode(', ', $a['reasons'] ?? []) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-4 text-center text-slate-400">Tidak ada anomali ditemukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@elseif ($analysis->analysis_type === 'timeline')
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="font-semibold mb-3">Jeda Waktu Mencurigakan</h2>
        @forelse (($result['suspicious_gaps'] ?? []) as $g)
            <p class="text-sm border-b py-1">{{ $g['from'] }} &rarr; {{ $g['to'] }} ({{ $g['gap_hours'] }} jam)</p>
        @empty
            <p class="text-sm text-slate-400">Tidak ada jeda waktu tidak wajar terdeteksi.</p>
        @endforelse
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-3">Urutan Kejadian ({{ $result['event_count'] ?? 0 }})</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b"><tr><th class="py-2">Waktu</th><th>Jenis Kejadian</th><th>Entitas</th></tr></thead>
            <tbody>
            @foreach (array_slice($result['events'] ?? [], 0, 100) as $ev)
                <tr class="border-b">
                    <td class="py-2">{{ $ev['timestamp'] }}</td>
                    <td>{{ $ev['event_type'] }}</td>
                    <td>{{ $ev['entity_id'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@elseif ($analysis->analysis_type === 'graph')
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-3">Entitas Paling Berpengaruh (Hub)</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b"><tr><th class="py-2">Entitas</th><th>Jumlah Koneksi</th><th>Centrality</th></tr></thead>
            <tbody>
            @foreach (($result['top_hubs'] ?? []) as $n)
                <tr class="border-b">
                    <td class="py-2">{{ $n['id'] }}</td>
                    <td>{{ $n['degree'] }}</td>
                    <td>{{ $n['degree_centrality'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="text-sm mt-4">Total: {{ $result['node_count'] ?? 0 }} entitas, {{ $result['edge_count'] ?? 0 }} relasi, {{ count($result['communities'] ?? []) }} klaster.</p>
    </div>
@endif
@endsection
