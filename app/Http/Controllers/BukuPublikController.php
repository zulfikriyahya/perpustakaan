<?php

namespace App\Http\Controllers;

use App\Enums\JenisFileBuku;
use App\Models\Buku;
use App\Models\BukuFile;
use Illuminate\Http\Request;

class BukuPublikController extends Controller
{
    public function index(Request $request)
    {
        // TODO: ASUMSI - pencarian dibatasi pada kolom 'judul' dan 'penulis'
        // (kolom string legacy di tabel bukus), tidak menyertakan 'isbn'
        // maupun relasi authors/kategoris karena tidak dispesifikasikan.
        // Jika perlu dicakup, tandai lanjutan sebagai gap baru.
        $search = trim((string) $request->query('q', ''));

        $ebooks = Buku::query()
            ->whereHas('files', fn($q) => $q->whereIn('jenis', [
                JenisFileBuku::Pdf->value,
                JenisFileBuku::Epub->value,
            ]))
            ->when($search !== '', fn($q) => $q->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            }))
            ->with('files')
            ->orderBy('judul')
            ->paginate(12, ['*'], 'ebook_page')
            ->withQueryString();

        $audiobooks = Buku::query()
            ->whereHas('files', fn($q) => $q->whereIn('jenis', [
                JenisFileBuku::AudioMp3->value,
                JenisFileBuku::AudioWav->value,
            ]))
            ->when($search !== '', fn($q) => $q->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            }))
            ->with('files')
            ->orderBy('judul')
            ->paginate(12, ['*'], 'audio_page')
            ->withQueryString();

        return view('buku.index', compact('ebooks', 'audiobooks', 'search'));
    }

    /**
     * TODO: GAP-SPEC - reader saat ini publik tanpa batasan (dikonfirmasi
     * akses publik tanpa login). Jika ke depan perlu dibatasi (mis. hanya
     * preview N halaman), perlu keputusan eksplisit lanjutan.
     *
     * Header X-Robots-Tag: noindex dipasang di response supaya halaman
     * reader per-file TIDAK di-index search engine (dianggap thin/duplicate
     * content dari halaman katalog buku.index yang sudah jadi halaman
     * kanonik) - dilakukan lewat header HTTP (bukan <meta name="robots">
     * di Blade) karena view buku.baca-pdf.blade.php belum ditinjau isinya
     * di sesi ini (Aturan poin 18) - pendekatan header aman tanpa perlu
     * menyentuh file itu.
     */
    public function baca(BukuFile $file)
    {
        abort_unless($file->jenis === JenisFileBuku::Pdf, 404);

        return response()
            ->view('buku.baca-pdf', [
                'file' => $file,
                'buku' => $file->buku,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
