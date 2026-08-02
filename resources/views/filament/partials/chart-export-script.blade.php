@once
<script>
window.ChartExport = window.ChartExport || (function () {
    function flattenToWhite(canvas) {
        // Canvas Chart.js transparan - flatten ke putih dulu supaya hasil
        // download tetap terbaca di luar dashboard (mode gelap dsb).
        const flat = document.createElement('canvas');
        flat.width = canvas.width;
        flat.height = canvas.height;

        const ctx = flat.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, flat.width, flat.height);
        ctx.drawImage(canvas, 0, 0);

        return flat;
    }

    function downloadImage(canvas, filename) {
        if (!canvas) {
            return;
        }

        const flat = flattenToWhite(canvas);
        const link = document.createElement('a');
        link.href = flat.toDataURL('image/png');
        link.download = filename + '.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function downloadPdf(canvas, filename, meta) {
    if (!canvas) {
        return;
    }

    meta = meta || {};

    const flat = flattenToWhite(canvas);
    const dataUrl = flat.toDataURL('image/png');
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    fetch('{{ route('chart-export.pdf') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken ?? '',
            Accept: 'application/pdf',
        },
        body: JSON.stringify({
            image: dataUrl,
            filename: filename,
            widget: meta.widget,
            type: meta.type,
            stat_label: meta.statLabel,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Gagal membuat PDF di server');
            }

            return response.blob();
        })
        .then((blob) => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename + '.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        })
        .catch(() => {
            alert('Gagal membuat PDF. Silakan coba lagi.');
        });
}

    return { downloadImage, downloadPdf };
})();
</script>
@endonce
