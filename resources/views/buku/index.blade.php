<x-layout :title="'Buku Digital'">
    <section class="max-w-6xl mx-auto px-4 py-16">
        <h1 class="text-3xl font-bold text-teal-900 mb-8">E-Book</h1>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($ebooks as $buku)
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="aspect-[3/4] bg-slate-100">
                        @if ($buku->cover)
                            <img src="{{ asset('storage/'.$buku->cover) }}"
                                 alt="{{ $buku->judul }}"
                                 class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="font-medium text-sm text-slate-700 truncate">{{ $buku->judul }}</p>
                        @foreach ($buku->files->where('jenis', \App\Enums\JenisFileBuku::Pdf) as $file)
                            <a href="{{ route('buku.baca', $file) }}"
                               class="inline-block mt-2 text-sm text-teal-700 hover:underline">
                                Baca PDF
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-slate-500">Belum ada e-book.</p>
            @endforelse
        </div>
        {{ $ebooks->links() }}

        <h1 class="text-3xl font-bold text-teal-900 mt-16 mb-8">Audiobook</h1>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($audiobooks as $buku)
                <div class="bg-white border rounded-lg overflow-hidden">
                    <div class="aspect-[3/4] bg-slate-100">
                        @if ($buku->cover)
                            <img src="{{ asset('storage/'.$buku->cover) }}"
                                 alt="{{ $buku->judul }}"
                                 class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="font-medium text-sm text-slate-700 truncate">{{ $buku->judul }}</p>
                        @foreach ($buku->files->filter(fn ($f) => $f->jenis->isAudio()) as $file)
                            <audio controls class="w-full mt-2">
                                <source src="{{ $file->url() }}">
                            </audio>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-slate-500">Belum ada audiobook.</p>
            @endforelse
        </div>
        {{ $audiobooks->links() }}
    </section>
</x-layout>
