<x-layout :title="'Authors'">
    <section class="max-w-6xl mx-auto px-4 py-16">
        <h1 class="text-3xl font-bold text-teal-900 mb-8">Penulis</h1>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($authors as $author)
                <a href="{{ route('authors.show', $author) }}"
                   class="bg-white border rounded-lg p-5 text-center hover:shadow-md transition">
                    <div class="w-24 h-24 rounded-full mx-auto bg-slate-100 overflow-hidden border">
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
                    <p class="mt-3 font-medium text-slate-800">{{ $author->nama }}</p>
                    <p class="text-sm text-slate-500">{{ $author->bukus_count }} buku</p>
                </a>
            @empty
                <p class="col-span-full text-center text-slate-500">Belum ada penulis.</p>
            @endforelse
        </div>
        {{ $authors->links() }}
    </section>
</x-layout>
