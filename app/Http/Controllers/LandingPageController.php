<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Buku;

class LandingPageController extends Controller
{
    public function index()
    {
        $bukuTerbaru = Buku::query()->latest()->take(8)->get();
        $authors = Author::query()->latest()->take(6)->get();

        return view('index', compact('bukuTerbaru', 'authors'));
    }

    public function faq()
    {
        return view('faq');
    }

    public function tentang()
    {
        return view('tentang');
    }
}
