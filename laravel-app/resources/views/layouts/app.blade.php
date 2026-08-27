<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { navy: '#0b3d91' } } } }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
@auth
    <div class="flex min-h-screen">
        <aside class="w-64 bg-navy text-white flex flex-col">
            <div class="p-5 border-b border-blue-900">
                <h1 class="font-bold text-lg leading-tight">Bea Cukai<br>Forensic Dashboard</h1>
            </div>
            <nav class="flex-1 p-3 space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-blue-900 {{ request()->routeIs('dashboard') ? 'bg-blue-900' : '' }}">📊 Dashboard</a>
                <a href="{{ route('cases.index') }}" class="block px-3 py-2 rounded hover:bg-blue-900 {{ request()->routeIs('cases.*') ? 'bg-blue-900' : '' }}">🗂️ Kasus Forensik</a>
                <a href="{{ route('cases.create') }}" class="block px-3 py-2 rounded hover:bg-blue-900">➕ Kasus Baru</a>
            </nav>
            <div class="p-4 border-t border-blue-900 text-xs">
                <p class="mb-2">{{ auth()->user()->name }} <span class="opacity-70">({{ auth()->user()->role }})</span></p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-red-200 hover:text-red-50">Keluar</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 p-8">
            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@else
    @yield('content')
@endauth
</body>
</html>
