<header class="bg-teal-800 text-white">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/brand-lightmode.png') }}" alt="Logo" class="h-10 w-auto">
        </a>
        <nav class="hidden md:flex gap-8 text-sm font-medium">
            <a href="{{ route('home') }}" class="hover:text-teal-200">Beranda</a>
            <a href="{{ route('katalog.index') }}" class="hover:text-teal-200">Buku</a>
            <a href="{{ route('buku.index') }}" class="hover:text-teal-200">Buku Digital</a>
            <a href="{{ route('authors.index') }}" class="hover:text-teal-200">Authors</a>
            <a href="{{ route('faq') }}" class="hover:text-teal-200">FAQ</a>
            <a href="{{ route('tentang') }}" class="hover:text-teal-200">Tentang</a>
        </nav>
        <a href="{{ url('dashboard') }}"
           class="bg-white text-teal-800 px-4 py-2 rounded-md text-sm font-semibold hover:bg-teal-50">
            Masuk
        </a>
    </div>
</header>
