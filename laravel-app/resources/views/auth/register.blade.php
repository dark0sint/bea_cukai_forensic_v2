@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-navy">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold text-navy mb-1">Buat Akun Investigator</h1>
        <p class="text-sm text-slate-500 mb-6">Akun pertama yang mendaftar otomatis menjadi Admin.</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-700 text-sm px-3 py-2">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <div>
                <label class="text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <div>
                <label class="text-sm font-medium">Kata Sandi</label>
                <input type="password" name="password" required
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <div>
                <label class="text-sm font-medium">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" required
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-navy text-white py-2 rounded font-medium hover:bg-blue-900">Daftar</button>
        </form>

        <p class="text-sm text-slate-500 mt-4 text-center">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-navy font-medium">Masuk</a>
        </p>
    </div>
</div>
@endsection
