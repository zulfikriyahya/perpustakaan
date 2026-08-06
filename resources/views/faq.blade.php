<x-layout :title="'FAQ'">
    <section class="max-w-3xl mx-auto px-4 py-16">
        <h1 class="text-3xl font-bold text-teal-900 mb-8">Pertanyaan yang Sering Diajukan</h1>

        <div class="space-y-6">
            <div class="bg-white border rounded-lg p-5">
                <h3 class="font-semibold text-slate-800">Bagaimana cara meminjam buku?</h3>
                <p class="text-slate-600 mt-1">Kunjungi perpustakaan dan lakukan tap kartu RFID di meja pustakawan.</p>
            </div>
            <div class="bg-white border rounded-lg p-5">
                <h3 class="font-semibold text-slate-800">Apakah e-book dan audiobook bisa diakses siapa saja?</h3>
                <p class="text-slate-600 mt-1">Ya, koleksi digital dapat diakses publik tanpa perlu login.</p>
            </div>
            {{-- TODO: GAP-SPEC - konten FAQ masih placeholder, tunggu materi resmi dari sekolah --}}
        </div>
    </section>
</x-layout>
