@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-navy">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold text-navy mb-1">Bea Cukai Forensic Dashboard</h1>
        <p class="text-sm text-slate-500 mb-6">Masuk untuk mengakses sistem analisis forensik.</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-700 text-sm px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <div>
                <label class="text-sm font-medium">Kata Sandi</label>
                <input type="password" name="password" required
                       class="w-full mt-1 border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-navy focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-navy text-white py-2 rounded font-medium hover:bg-blue-900">Masuk</button>
        </form>

        <p class="text-sm text-slate-500 mt-4 text-center">
            Belum punya akun? <a href="{{ route('register') }}" class="text-navy font-medium">Daftar</a>
        </p>
    </div>
</div>
@endsection
