<x-layout
    :title="'Katalog Buku'"
    :description="'Katalog koleksi buku fisik Perpustakaan MTs Negeri 1 Pandeglang.'"
>
    <section class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-3xl md:text-4xl font-bold text-teal-900">Katalog Buku</h1>
            <p class="mt-2 text-slate-600 max-w-2xl">
                Jelajahi koleksi buku fisik perpustakaan kami.
            </p>

            <form action="{{ route('katalog.index') }}" method="GET" class="mt-6 max-w-lg">
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari judul atau penulis..."
                        class="w-full rounded-md border border-slate-300 pl-10 pr-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.35 4.35a7.5 7.5 0 0012.3 12.3z" />
                    </svg>
                </div>
                @if ($search !== '')
                    <a href="{{ route('katalog.index') }}" class="inline-block mt-2 text-xs text-slate-500 hover:text-teal-700 hover:underline">
                        &times; Hapus pencarian "{{ $search }}"
                    </a>
                @endif
            </form>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-14">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($bukus as $buku)
                <a href="{{ route('katalog.show', $buku) }}"
                   class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                    <div class="aspect-[3/4] bg-slate-100">
                        @if ($buku->cover)
                            <img src="{{ asset('storage/'.$buku->cover) }}"
                                 alt="{{ $buku->judul }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                Tanpa Sampul
                            </div>
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="font-medium text-sm text-slate-800 truncate" title="{{ $buku->judul }}">{{ $buku->judul }}</p>
                        @if ($buku->penulis)
                            <p class="text-xs text-slate-500 truncate">{{ $buku->penulis }}</p>
                        @endif
                        <p class="text-xs text-teal-700 mt-2">
                            {{ $buku->eksemplars_count }} eksemplar
                        </p>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-slate-500">
                        @if ($search !== '')
                            Tidak ada buku yang cocok dengan "{{ $search }}".
                        @else
                            Belum ada buku.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">
            {{ $bukus->links() }}
        </div>
    </section>
</x-layout>
