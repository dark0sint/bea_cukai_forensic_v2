@extends('layouts.app')
@section('title', 'Kasus Baru')
@section('content')
<h1 class="text-2xl font-bold text-navy mb-6">Buat Kasus Forensik Baru</h1>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form method="POST" action="{{ route('cases.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm font-medium">Judul Kasus</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none"
                   placeholder="mis. Dugaan Under-invoicing Impor Tekstil PT XYZ">
        </div>
        <div>
            <label class="text-sm font-medium">Deskripsi</label>
            <textarea name="description" rows="4"
                      class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">{{ old('description') }}</textarea>
        </div>
        <div>
            <label class="text-sm font-medium">Prioritas</label>
            <select name="priority" class="w-full mt-1 border rounded px-3 py-2 text-sm">
                <option value="low">Rendah</option>
                <option value="medium" selected>Sedang</option>
                <option value="high">Tinggi</option>
                <option value="critical">Kritis</option>
            </select>
        </div>
        <button type="submit" class="bg-navy text-white px-5 py-2 rounded text-sm hover:bg-blue-900">Simpan Kasus</button>
    </form>
</div>
@endsection
