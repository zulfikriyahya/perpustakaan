@php
    $daftarFaq = [
        [
            'tanya' => 'Bagaimana cara meminjam buku?',
            'jawab' => 'Kunjungi perpustakaan dan lakukan tap kartu RFID di meja pustakawan.',
        ],
        [
            'tanya' => 'Apakah e-book dan audiobook bisa diakses siapa saja?',
            'jawab' => 'Ya, koleksi digital dapat diakses publik tanpa perlu login.',
        ],
        // TODO: GAP-SPEC - konten FAQ masih placeholder, tunggu materi resmi dari sekolah
    ];
@endphp
<x-layout
    :title="'FAQ'"
    :description="'Pertanyaan yang sering diajukan seputar layanan Perpustakaan Digital MTs Negeri 1 Pandeglang.'"
>
    @push('jsonld')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($daftarFaq)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['tanya'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['jawab'],
            ],
        ])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

    <section class="max-w-3xl mx-auto px-4 py-16">
        <h1 class="text-3xl font-bold text-teal-900 mb-8">Pertanyaan yang Sering Diajukan</h1>

        <div class="space-y-6">
            @foreach ($daftarFaq as $faq)
                <div class="bg-white border rounded-lg p-5">
                    <h3 class="font-semibold text-slate-800">{{ $faq['tanya'] }}</h3>
                    <p class="text-slate-600 mt-1">{{ $faq['jawab'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>
