<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Buku;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('katalog.index'), 'priority' => '0.9'],
            ['loc' => route('buku.index'), 'priority' => '0.9'],
            ['loc' => route('authors.index'), 'priority' => '0.8'],
            ['loc' => route('faq'), 'priority' => '0.5'],
            ['loc' => route('tentang'), 'priority' => '0.5'],
        ]);

        $authorUrls = Author::query()
            ->select('id', 'updated_at')
            ->get()
            ->map(fn (Author $author) => [
                'loc' => route('authors.show', $author),
                'priority' => '0.6',
                'lastmod' => $author->updated_at?->toAtomString(),
            ]);

        $bukuUrls = Buku::query()
            ->select('id', 'updated_at')
            ->get()
            ->map(fn (Buku $buku) => [
                'loc' => route('katalog.show', $buku),
                'priority' => '0.6',
                'lastmod' => $buku->updated_at?->toAtomString(),
            ]);

        $urls = $urls->concat($authorUrls)->concat($bukuUrls);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap', ['urls' => $urls])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
