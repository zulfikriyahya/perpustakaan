<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Perpustakaan Digital' }} - MTs Negeri 1 Pandeglang</title>
    <meta name="description" content="{{ $description ?? 'Perpustakaan digital MTs Negeri 1 Pandeglang - katalog buku, e-book, dan audiobook.' }}">

    {{-- BARU (gap iterasi ini: finalisasi SEO) - robots meta per-halaman,
         default selalu "index, follow" kecuali halaman secara eksplisit
         mengirim prop $robots (mis. halaman reader PDF via header HTTP,
         bukan lewat prop ini - lihat BukuPublikController::baca()). --}}
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">

    {{-- BARU - canonical URL, default ke URL saat ini (tanpa query string
         pagination dsb DIHILANGKAN secara sengaja - canonical selalu
         menunjuk versi "bersih" dari halaman, kecuali di-override manual
         lewat prop $canonical, mis. utk halaman terpaginasi jika suatu
         saat ingin canonical ke halaman 1). --}}
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- BARU - Open Graph (Facebook/WhatsApp/dll share preview) --}}
    <meta property="og:site_name" content="Perpustakaan Digital MTs Negeri 1 Pandeglang">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $title ?? 'Perpustakaan Digital' }} - MTs Negeri 1 Pandeglang">
    <meta property="og:description" content="{{ $description ?? 'Perpustakaan digital MTs Negeri 1 Pandeglang - katalog buku, e-book, dan audiobook.' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    {{-- TODO: GAP-SPEC - belum ada aset og:image resmi berukuran ideal
         (disarankan 1200x630px). Fallback sementara pakai favicon
         (ukurannya jauh dari ideal utk preview link, tapi lebih baik
         daripada tidak ada gambar sama sekali). Ganti asset('images/favicon.ico')
         di bawah begitu aset og-image resmi tersedia dari sekolah. --}}
    <meta property="og:image" content="{{ $ogImage ?? asset('images/favicon.ico') }}">

    {{-- BARU - Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Perpustakaan Digital' }} - MTs Negeri 1 Pandeglang">
    <meta name="twitter:description" content="{{ $description ?? 'Perpustakaan digital MTs Negeri 1 Pandeglang - katalog buku, e-book, dan audiobook.' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/favicon.ico') }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="{{ asset('images/favicon.ico') }}">
    @vite('resources/css/app.css')

    {{-- BARU - slot khusus JSON-LD structured data, diisi opsional oleh
         masing-masing view lewat @@push('jsonld') ... @@endpush, dirender
         di <head> supaya search engine bisa membaca structured data
         tanpa perlu parsing seluruh body. --}}
    @stack('jsonld')
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
