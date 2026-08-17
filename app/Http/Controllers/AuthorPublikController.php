<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorPublikController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $authors = Author::query()
            ->withCount('bukus')
            ->when($search !== '', fn($q) => $q->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();

        return view('authors', compact('authors', 'search'));
    }

    public function show(Author $author)
    {
        $author->load('bukus');

        return view('author-detail', compact('author'));
    }
}
