<footer class="bg-teal-900 text-teal-100 mt-16">
    <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-3 gap-8 text-sm">
        <div>
            <h3 class="font-semibold text-white mb-2">MTs Negeri 1 Pandeglang</h3>
            <p class="text-teal-200">Perpustakaan digital - koleksi fisik, e-book, dan audiobook.</p>
        </div>
        <div>
            <h3 class="font-semibold text-white mb-2">Tautan</h3>
            <ul class="space-y-1 text-teal-200">
                <li><a href="{{ route('buku.index') }}" class="hover:text-white">Buku Digital</a></li>
                <li><a href="{{ route('authors.index') }}" class="hover:text-white">Authors</a></li>
                <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                <li><a href="{{ route('tentang') }}" class="hover:text-white">Tentang Perpustakaan</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-semibold text-white mb-2">Kontak</h3>
            {{-- TODO: GAP-SPEC - alamat/kontak resmi belum tersedia, isi placeholder --}}
            <p class="text-teal-200">Jl. Raya Labuan Km 5,7 Palurahan, Kaduhejo, Pandeglang, Banten</p>
        </div>
    </div>
    <div class="border-t border-teal-800 text-center text-xs text-teal-300 py-4">
        &copy; {{ date('Y') }} MTs Negeri 1 Pandeglang. Seluruh hak cipta dilindungi.
    </div>
</footer>
