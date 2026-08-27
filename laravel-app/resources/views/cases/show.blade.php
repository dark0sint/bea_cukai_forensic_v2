@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-2xl font-bold text-navy">{{ $case->case_number }} - {{ $case->title }}</h1>
        <p class="text-slate-500 text-sm mt-1">{{ $case->description }}</p>
    </div>
    <div class="flex gap-2 items-center">
        <form method="POST" action="{{ route('cases.status', $case) }}">
            @csrf @method('PATCH')
            <select name="status" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                @foreach(['open'=>'Terbuka','in_progress'=>'Diproses','closed'=>'Ditutup','archived'=>'Diarsipkan'] as $val=>$label)
                    <option value="{{ $val }}" @selected($case->status === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('reports.generate', $case) }}" class="bg-navy text-white px-4 py-2 rounded text-sm hover:bg-blue-900">📄 Unduh Laporan PDF</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Upload Evidence -->
    <div class="bg-white rounded-lg shadow p-5 lg:col-span-1">
        <h2 class="font-semibold mb-3">Unggah Barang Bukti</h2>
        <form method="POST" action="{{ route('evidence.store', $case) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="file" required accept=".csv,.json,.xml,.pdf"
                   class="w-full text-sm border rounded px-2 py-2">
            <p class="text-xs text-slate-400">Format didukung: CSV, JSON, XML, PDF. Maks 50MB.</p>
            <button type="submit" class="w-full bg-navy text-white py-2 rounded text-sm hover:bg-blue-900">Unggah &amp; Proses</button>
        </form>

        <h3 class="font-medium mt-6 mb-2 text-sm">Daftar Barang Bukti</h3>
        <ul class="space-y-2 text-sm max-h-96 overflow-y-auto">
            @forelse ($case->evidenceItems as $ev)
                <li class="border rounded p-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium">{{ $ev->original_filename }}</p>
                            <p class="text-xs text-slate-400">{{ strtoupper($ev->file_type) }} · {{ number_format($ev->size_bytes/1024,1) }} KB</p>
                            <p class="text-xs font-mono text-slate-400 break-all">SHA256: {{ Str::limit($ev->sha256, 20) }}</p>
                        </div>
                        <form method="POST" action="{{ route('evidence.destroy', [$case, $ev]) }}" onsubmit="return confirm('Hapus barang bukti ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 text-xs hover:underline">Hapus</button>
                        </form>
                    </div>

                    <details class="mt-2">
                        <summary class="text-xs text-navy cursor-pointer">Jalankan Analisis</summary>
                        <div class="mt-2 space-y-2">
                            <form method="POST" action="{{ route('analysis.anomaly', [$case, $ev]) }}">
                                @csrf
                                <button class="w-full text-xs bg-orange-100 text-orange-700 py-1.5 rounded hover:bg-orange-200">🔍 Deteksi Anomali</button>
                            </form>
                            <form method="POST" action="{{ route('analysis.timeline', [$case, $ev]) }}">
                                @csrf
                                <button class="w-full text-xs bg-blue-100 text-blue-700 py-1.5 rounded hover:bg-blue-200">🕒 Bangun Timeline</button>
                            </form>
                            <form method="POST" action="{{ route('analysis.graph', [$case, $ev]) }}" class="space-y-1">
                                @csrf
                                <input name="source_field" placeholder="kolom sumber (mis. importir)" required class="w-full text-xs border rounded px-2 py-1">
                                <input name="target_field" placeholder="kolom tujuan (mis. eksportir)" required class="w-full text-xs border rounded px-2 py-1">
                                <button class="w-full text-xs bg-purple-100 text-purple-700 py-1.5 rounded hover:bg-purple-200">🕸️ Analisis Graf Relasi</button>
                            </form>
                        </div>
                    </details>
                </li>
            @empty
                <li class="text-slate-400 text-sm">Belum ada barang bukti diunggah.</li>
            @endforelse
        </ul>
    </div>

    <!-- Analysis Results -->
    <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <h2 class="font-semibold mb-3">Hasil Analisis</h2>
        <div class="space-y-3">
            @forelse ($case->analysisResults->sortByDesc('created_at') as $analysis)
                <a href="{{ route('analysis.show', [$case, $analysis]) }}" class="block border rounded p-3 hover:bg-slate-50">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-sm capitalize">
                            @if($analysis->analysis_type === 'anomaly') 🔍 Deteksi Anomali
                            @elseif($analysis->analysis_type === 'timeline') 🕒 Timeline
                            @else 🕸️ Graf Relasi
                            @endif
                        </span>
                        <span class="text-xs text-slate-400">{{ $analysis->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">{{ $analysis->result['summary'] ?? '-' }}</p>
                </a>
            @empty
                <p class="text-slate-400 text-sm">Belum ada analisis dijalankan pada kasus ini.</p>
            @endforelse
        </div>

        <h2 class="font-semibold mt-8 mb-3">Riwayat Aktivitas (Chain of Custody)</h2>
        <ul class="text-sm space-y-2 max-h-64 overflow-y-auto">
            @foreach ($case->auditLogs->sortByDesc('created_at') as $log)
                <li class="border-b pb-1">
                    <span class="font-medium">{{ $log->user->name ?? 'System' }}</span> — {{ $log->description }}
                    <span class="block text-xs text-slate-400">{{ $log->created_at->format('d M Y H:i') }} · IP: {{ $log->ip_address }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
