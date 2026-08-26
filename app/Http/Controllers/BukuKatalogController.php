<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

class BukuKatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $bukus = Buku::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
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
