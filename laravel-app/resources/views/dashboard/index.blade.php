@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-navy">Dashboard</h1>
    <span class="text-xs px-3 py-1 rounded-full {{ ($serviceStatus['status'] ?? '') === 'ok' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
        Python Service: {{ $serviceStatus['status'] ?? 'unreachable' }}
    </span>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-slate-500">Total Kasus</p>
        <p class="text-3xl font-bold text-navy">{{ $stats['total_cases'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-slate-500">Kasus Terbuka</p>
        <p class="text-3xl font-bold text-orange-500">{{ $stats['open_cases'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-slate-500">Barang Bukti</p>
        <p class="text-3xl font-bold text-navy">{{ $stats['total_evidence'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-slate-500">Analisis Dijalankan</p>
        <p class="text-3xl font-bold text-navy">{{ $stats['total_analysis'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-3">Kasus Terbaru</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-500 border-b"><th class="py-2">Nomor</th><th>Judul</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentCases as $case)
                <tr class="border-b last:border-0">
                    <td class="py-2"><a href="{{ route('cases.show', $case) }}" class="text-navy hover:underline">{{ $case->case_number }}</a></td>
                    <td>{{ $case->title }}</td>
                    <td><span class="text-xs px-2 py-1 rounded bg-slate-100">{{ $case->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-4 text-center text-slate-400">Belum ada kasus.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-3">Aktivitas Terbaru (Audit Log)</h2>
        <ul class="text-sm space-y-2">
            @forelse ($recentActivity as $log)
                <li class="border-b pb-2 last:border-0">
                    <span class="font-medium">{{ $log->user->name ?? 'System' }}</span> - {{ $log->description }}
                    <span class="block text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="text-slate-400">Belum ada aktivitas.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
