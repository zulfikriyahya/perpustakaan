<?php

namespace App\Http\Controllers;

use App\Enums\JenisFileBuku;
use App\Models\Buku;
use App\Models\BukuFile;

class BukuPublikController extends Controller
{
    public function index()
    {
        $ebooks = Buku::query()
            ->whereHas('files', fn($q) => $q->whereIn('jenis', [
                JenisFileBuku::Pdf->value,
                JenisFileBuku::Epub->value,
            ]))
            ->with('files')
            ->paginate(12, ['*'], 'ebook_page');

        $audiobooks = Buku::query()
            ->whereHas('files', fn($q) => $q->whereIn('jenis', [
                JenisFileBuku::AudioMp3->value,
                JenisFileBuku::AudioWav->value,
            ]))
            ->with('files')
            ->paginate(12, ['*'], 'audio_page');

        return view('buku.index', compact('ebooks', 'audiobooks'));
    }

    /**
     * TODO: GAP-SPEC - reader saat ini publik tanpa batasan (dikonfirmasi
     * akses publik tanpa login). Jika ke depan perlu dibatasi (mis. hanya
     * preview N halaman), perlu keputusan eksplisit lanjutan.
     *
     * BARU (gap iterasi ini, SEO): header X-Robots-Tag: noindex dipasang
     * di response supaya halaman reader per-file TIDAK di-index search
     * engine (dianggap thin/duplicate content dari halaman katalog
     * buku.index yang sudah jadi halaman kanonik) - dilakukan lewat
     * header HTTP (bukan <meta name="robots"> di Blade) karena view
     * buku.baca-pdf.blade.php belum ditinjau isinya di sesi ini (Aturan
     * poin 18) - pendekatan header aman tanpa perlu menyentuh file itu.
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
