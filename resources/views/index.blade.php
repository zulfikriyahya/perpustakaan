<x-layout :title="'Beranda'">
    <section class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-20 text-center">
            <h1 class="text-4xl font-bold text-teal-900">Perpustakaan Digital Sekolah</h1>
            <p class="mt-4 text-slate-600 max-w-xl mx-auto">
                Jelajahi koleksi buku fisik, e-book, dan audiobook MTs Negeri 1 Pandeglang.
            </p>
            <a href="{{ route('buku.index') }}"
               class="inline-block mt-8 px-6 py-3 bg-teal-700 text-white rounded-md font-medium hover:bg-teal-800">
                Lihat Buku Digital
            </a>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-16">
        <div class="flex justify-between items-end mb-8">
            <h2 class="text-2xl font-semibold text-teal-900">Buku Terbaru</h2>
            <a href="{{ route('buku.index') }}" class="text-sm text-teal-700 hover:underline">Lihat semua</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($bukuTerbaru as $buku)
                <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
                    <div class="aspect-[3/4] bg-slate-100">
                        @if ($buku->cover)
                            <img src="{{ asset('storage/'.$buku->cover) }}"
                                 alt="{{ $buku->judul }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                Tanpa Sampul
                            </div>
                        @endif
                    </div>
                    <p class="p-3 font-medium text-sm text-slate-700 truncate">{{ $buku->judul }}</p>
                </div>
            @empty
                <p class="col-span-full text-center text-slate-500">Belum ada buku.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-white border-t">
        <div class="max-w-6xl mx-auto px-4 py-16">
            <div class="flex justify-between items-end mb-8">
                <h2 class="text-2xl font-semibold text-teal-900">Penulis</h2>
                <a href="{{ route('authors.index') }}" class="text-sm text-teal-700 hover:underline">Lihat semua</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-6 text-center">
                @forelse ($authors as $author)
                    <a href="{{ route('authors.show', $author) }}" class="group">
                        <div class="w-20 h-20 rounded-full mx-auto bg-slate-100 overflow-hidden border">
                            @if ($author->foto)
                                <img src="{{ asset('storage/'.$author->foto) }}"
                                     alt="{{ $author->nama }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                    {{ mb_substr($author->nama, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-slate-700 group-hover:text-teal-700">{{ $author->nama }}</p>
                    </a>
                @empty
                    <p class="col-span-full text-center text-slate-500">Belum ada penulis.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-layout>
