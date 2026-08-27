@extends('layouts.app')
@section('title', 'Kasus Forensik')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-navy">Kasus Forensik</h1>
    <a href="{{ route('cases.create') }}" class="bg-navy text-white px-4 py-2 rounded text-sm hover:bg-blue-900">+ Kasus Baru</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="p-3">Nomor Kasus</th>
                <th>Judul</th>
                <th>Prioritas</th>
                <th>Status</th>
                <th>Bukti</th>
                <th>Dibuat</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($cases as $case)
            <tr class="border-t hover:bg-slate-50">
                <td class="p-3"><a href="{{ route('cases.show', $case) }}" class="text-navy font-medium hover:underline">{{ $case->case_number }}</a></td>
                <td>{{ $case->title }}</td>
                <td><span class="text-xs px-2 py-1 rounded bg-orange-100 text-orange-700">{{ $case->priority }}</span></td>
                <td><span class="text-xs px-2 py-1 rounded bg-slate-100">{{ $case->status }}</span></td>
                <td>{{ $case->evidenceItems->count() }}</td>
                <td>{{ $case->created_at->format('d M Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $cases->links() }}</div>
@endsection
