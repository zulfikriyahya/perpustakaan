<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Baca {{ $buku->judul }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-900 text-white">
    <div class="flex justify-between items-center p-4">
        <h1 class="text-lg font-semibold">{{ $buku->judul }}</h1>
        <a href="{{ route('buku.index') }}" class="text-sm underline">Kembali</a>
    </div>

    <div id="flipbook-container" class="flex flex-col items-center gap-4 pb-12">
        <canvas id="pdf-canvas" class="shadow-lg bg-white"></canvas>
        <div class="flex gap-4">
            <button id="prev-page" class="px-4 py-2 bg-teal-600 rounded">Sebelumnya</button>
            <span id="page-info" class="self-center"></span>
            <button id="next-page" class="px-4 py-2 bg-teal-600 rounded">Selanjutnya</button>
        </div>
    </div>

    <script type="module">
        import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4/build/pdf.min.mjs';
        // TODO: GAP-SPEC - saat ini pdf.js dimuat via CDN untuk iterasi awal;
        // rencana build lokal via package.json (pdfjs-dist) + Vite belum
        // diintegrasikan penuh (worker path perlu konfigurasi khusus Vite).
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdn.jsdelivr.net/npm/pdfjs-dist@4/build/pdf.worker.min.mjs';

        const url = @json($file->url());
        let pdfDoc = null;
        let pageNum = 1;

        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');

        function renderPage(num) {
            pdfDoc.getPage(num).then(function (page) {
                const viewport = page.getViewport({ scale: 1.3 });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({ canvasContext: ctx, viewport });
                document.getElementById('page-info').textContent = `Halaman ${num} / ${pdfDoc.numPages}`;
            });
        }

        pdfjsLib.getDocument(url).promise.then(function (doc) {
            pdfDoc = doc;
            renderPage(pageNum);
        });

        document.getElementById('prev-page').addEventListener('click', () => {
            if (pageNum <= 1) return;
            pageNum--;
            renderPage(pageNum);
        });

        document.getElementById('next-page').addEventListener('click', () => {
            if (pdfDoc && pageNum >= pdfDoc.numPages) return;
            pageNum++;
            renderPage(pageNum);
        });
    </script>
</body>
</html>
