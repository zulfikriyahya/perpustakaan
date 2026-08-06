<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('buku.index'), 'priority' => '0.9'],
            ['loc' => route('authors.index'), 'priority' => '0.8'],
            ['loc' => route('faq'), 'priority' => '0.5'],
            ['loc' => route('tentang'), 'priority' => '0.5'],
        ]);

        $authorUrls = Author::query()
            ->select('id', 'updated_at')
            ->get()
            ->map(fn(Author $author) => [
                'loc' => route('authors.show', $author),
                'priority' => '0.6',
                'lastmod' => $author->updated_at?->toAtomString(),
            ]);

        $urls = $urls->concat($authorUrls);

        $xml = view('sitemap', ['urls' => $urls])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
