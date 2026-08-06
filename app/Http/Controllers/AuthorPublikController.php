<?php

namespace App\Http\Controllers;

use App\Models\Author;

class AuthorPublikController extends Controller
{
    public function index()
    {
        $authors = Author::query()->withCount('bukus')->paginate(12);

        return view('authors', compact('authors'));
    }

    public function show(Author $author)
    {
        $author->load('bukus');

        return view('author-detail', compact('author'));
    }
}
