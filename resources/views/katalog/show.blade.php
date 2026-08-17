<x-layout
    :title="$buku->judul"
    :description="\Illuminate\Support\Str::limit(strip_tags($buku->deskripsi ?? 'Detail buku '.$buku->judul.' di Perpustakaan Digital MTs Negeri 1 Pandeglang.'), 160)"
    :og-image="$buku->cover ? asset('storage/'.$buku->cover) : null"
>
    @push('jsonld')
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Book',
        'name' => $buku->judul,
        'author' => $buku->penulis,
        'isbn' => $buku->isbn,
        'description' => $buku->deskripsi,
        'url' => route('katalog.show', $buku),
        'image' => $buku->cover ? asset('storage/'.$buku->cover) : null,
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

    <section class="max-w-4xl mx-auto px-4 py-16">
        <div class="bg-white border rounded-lg p-6 flex flex-col md:flex-row gap-6">
            <div class="w-full md:w-56 shrink-0">
                <div class="aspect-[3/4] bg-slate-100 rounded-md overflow-hidden">
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
            </div>

            <div class="flex-1">
                <h1 class="text-2xl font-bold text-teal-900">{{ $buku->judul }}</h1>

                @if ($buku->penulis)
                    <p class="mt-1 text-slate-600">Oleh {{ $buku->penulis }}</p>
                @endif

                @if ($buku->authors->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($buku->authors as $author)
                            <a href="{{ route('authors.show', $author) }}"
                               class="text-xs bg-teal-50 text-teal-700 px-2 py-1 rounded-full hover:bg-teal-100">
                                {{ $author->nama }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($buku->kategoris->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($buku->kategoris as $kategori)
                            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-full">
                                {{ $kategori->nama }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm text-slate-600">
                    @if ($buku->penerbit)
                        <div><dt class="text-slate-400">Penerbit</dt><dd>{{ $buku->penerbit }}</dd></div>
                    @endif
                    @if ($buku->tahun_terbit)
                        <div><dt class="text-slate-400">Tahun Terbit</dt><dd>{{ $buku->tahun_terbit }}</dd></div>
                    @endif
                    @if ($buku->isbn)
                        <div><dt class="text-slate-400">ISBN</dt><dd>{{ $buku->isbn }}</dd></div>
                    @endif
                    <div>
                        <dt class="text-slate-400">Stok Tersedia</dt>
                        <dd>{{ $buku->stokTersedia() }} dari {{ $buku->jumlahEksemplarAktif() }} eksemplar</dd>
                    </div>
                </dl>

                @if ($buku->deskripsi)
                    <p class="mt-4 text-slate-600 leading-relaxed">{{ $buku->deskripsi }}</p>
                @endif

                @if ($buku->files->isNotEmpty())
                    <a href="{{ route('buku.index') }}"
                       class="inline-block mt-6 text-sm font-medium text-teal-700 hover:underline">
                        Buku ini juga tersedia dalam versi digital &rarr;
                    </a>
                @endif
            </div>
        </div>
    </section>
</x-layout>
