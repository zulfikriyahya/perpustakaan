<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Perpustakaan Digital' }} - MTs Negeri 1 Pandeglang</title>
    <meta name="description" content="{{ $description ?? 'Perpustakaan digital MTs Negeri 1 Pandeglang - katalog buku, e-book, dan audiobook.' }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="{{ asset('images/favicon.ico') }}">
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col">
    @include('partials.public-nav')

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.public-footer')

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' });
        }
    </script>
</body>
</html>
