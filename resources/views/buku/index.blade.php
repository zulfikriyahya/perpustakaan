<x-layout
    :title="'Buku Digital'"
    :description="'Koleksi e-book dan audiobook digital Perpustakaan MTs Negeri 1 Pandeglang, dapat diakses publik tanpa login.'"
>
    <section class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-3xl md:text-4xl font-bold text-teal-900">Buku Digital</h1>
            <p class="mt-2 text-slate-600 max-w-2xl">
                Cari dan baca koleksi e-book serta audiobook kami secara gratis.
            </p>

            <form action="{{ route('buku.index') }}" method="GET" class="mt-6 max-w-lg">
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
                    <a href="{{ route('buku.index') }}" class="inline-block mt-2 text-xs text-slate-500 hover:text-teal-700 hover:underline">
                        &times; Hapus pencarian "{{ $search }}"
                    </a>
                @endif
            </form>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-14">
        <h2 class="text-2xl font-semibold text-teal-900 mb-6">E-Book</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($ebooks as $buku)
                <div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
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
                        @foreach ($buku->files->where('jenis', \App\Enums\JenisFileBuku::Pdf) as $file)
                            <a href="{{ route('buku.baca', $file) }}"
                               class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-teal-700 hover:text-teal-900 hover:underline">
                                Baca PDF
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-slate-500">
                        @if ($search !== '')
                            Tidak ada e-book yang cocok dengan "{{ $search }}".
                        @else
                            Belum ada e-book.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">
            {{ $ebooks->links() }}
        </div>

        <h2 class="text-2xl font-semibold text-teal-900 mt-16 mb-6">Audiobook</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($audiobooks as $buku)
                <div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
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
                        @foreach ($buku->files->filter(fn ($f) => $f->jenis->isAudio()) as $file)
                            <audio controls preload="none" class="w-full mt-3">
                                <source src="{{ $file->url() }}">
                            </audio>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-slate-500">
                        @if ($search !== '')
                            Tidak ada audiobook yang cocok dengan "{{ $search }}".
                        @else
                            Belum ada audiobook.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">
            {{ $audiobooks->links() }}
        </div>
    </section>
</x-layout>
