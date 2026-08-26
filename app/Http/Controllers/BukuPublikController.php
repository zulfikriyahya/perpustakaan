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
        $search = trim((string) $request->query('q', ''));

        $ebooks = Buku::query()
            ->whereHas('files', fn ($q) => $q->whereIn('jenis', [
                JenisFileBuku::Pdf->value,
                JenisFileBuku::Epub->value,
            ]))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            }))
            ->with('files')
            ->orderBy('judul')
            ->paginate(12, ['*'], 'ebook_page')
            ->withQueryString();

        $audiobooks = Buku::query()
            ->whereHas('files', fn ($q) => $q->whereIn('jenis', [
                JenisFileBuku::AudioMp3->value,
                JenisFileBuku::AudioWav->value,
            ]))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            }))
            ->with('files')
            ->orderBy('judul')
            ->paginate(12, ['*'], 'audio_page')
            ->withQueryString();

        return view('buku.index', compact('ebooks', 'audiobooks', 'search'));
    }

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
