<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

/**
 * Katalog buku FISIK (terpisah dari BukuPublikController yang khusus
 * e-book/audiobook digital). Menampilkan SEMUA buku (dikonfirmasi),
 * termasuk yang hanya punya file digital tanpa eksemplar fisik.
 */
class BukuKatalogController extends Controller
{
    public function index(Request $request)
    {
        // TODO: ASUMSI - pencarian dibatasi pada kolom 'judul' dan 'penulis',
        // sama seperti BukuPublikController, untuk konsistensi UX.
        $search = trim((string) $request->query('q', ''));

        $bukus = Buku::query()
            ->when($search !== '', fn($q) => $q->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            }))
            ->withCount(['eksemplars'])
            ->orderBy('judul')
            ->paginate(12)
            ->withQueryString();

        return view('katalog.index', compact('bukus', 'search'));
    }

    public function show(Buku $buku)
    {
        $buku->load(['authors', 'kategoris', 'files']);

        return view('katalog.show', compact('buku'));
    }
}
