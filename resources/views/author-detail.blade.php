<x-layout
    :title="$author->nama"
    :description="\Illuminate\Support\Str::limit(strip_tags($author->bio ?? 'Profil dan daftar buku karya '.$author->nama.' di Perpustakaan Digital MTs Negeri 1 Pandeglang.'), 160)"
    :og-image="$author->foto ? asset('storage/'.$author->foto) : null"
>
    @push('jsonld')
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $author->nama,
        'description' => $author->bio,
        'url' => route('authors.show', $author),
        'image' => $author->foto ? asset('storage/'.$author->foto) : null,
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush
    <section class="max-w-3xl mx-auto px-4 py-16">
        <div class="bg-white border rounded-lg p-6 flex items-center gap-6">
            <div class="w-24 h-24 rounded-full bg-slate-100 overflow-hidden border shrink-0">
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
            <div>
                <h1 class="text-2xl font-bold text-teal-900">{{ $author->nama }}</h1>
                <p class="mt-2 text-slate-600">{{ $author->bio }}</p>
            </div>
        </div>

        <h2 class="text-xl font-semibold text-teal-900 mt-10 mb-4">Buku</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse ($author->bukus as $buku)
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="aspect-[3/4] bg-slate-100">
                        @if ($buku->cover)
                            <img src="{{ asset('storage/'.$buku->cover) }}"
                                 alt="{{ $buku->judul }}"
                                 class="w-full h-full object-cover">
                        @endif
                    </div>
                    <p class="p-2 text-sm font-medium text-slate-700 truncate">{{ $buku->judul }}</p>
                </div>
            @empty
                <p class="col-span-full text-slate-500">Belum ada buku terkait.</p>
            @endforelse
        </div>
    </section>
</x-layout>
