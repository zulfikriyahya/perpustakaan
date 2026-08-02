import { jsPDF } from "jspdf";

// Logika export grafik terpusat - dipakai oleh chart-widget.blade.php dan
// stats-overview-widget/stat.blade.php (override) supaya tidak duplikasi
// logika toDataURL/jsPDF di banyak tempat (Aturan poin 3).

function flattenToWhiteBackground(canvas) {
    // Canvas Chart.js transparan - flatten ke putih dulu supaya PNG/PDF
    // hasil download tetap terbaca di luar dashboard (mode gelap dsb).
    const flattened = document.createElement("canvas");
    flattened.width = canvas.width;
    flattened.height = canvas.height;

    const ctx = flattened.getContext("2d");
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, flattened.width, flattened.height);
    ctx.drawImage(canvas, 0, 0);

    return flattened;
}

function triggerDownload(dataUrl, filename) {
    const link = document.createElement("a");
    link.href = dataUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadChartImage(canvas, filename) {
    if (!canvas) {
        return;
    }

    const flattened = flattenToWhiteBackground(canvas);
    triggerDownload(flattened.toDataURL("image/png"), `${filename}.png`);
}

function downloadChartPdf(canvas, filename) {
    if (!canvas) {
        return;
    }

    const flattened = flattenToWhiteBackground(canvas);
    const imgData = flattened.toDataURL("image/png");
    const isLandscape = canvas.width >= canvas.height;

    const pdf = new jsPDF({
        orientation: isLandscape ? "landscape" : "portrait",
        unit: "pt",
    });

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 24;
    const maxWidth = pageWidth - margin * 2;
    const maxHeight = pageHeight - margin * 2;
    const ratio = Math.min(maxWidth / canvas.width, maxHeight / canvas.height);
    const drawWidth = canvas.width * ratio;
    const drawHeight = canvas.height * ratio;
    const x = (pageWidth - drawWidth) / 2;
    const y = (pageHeight - drawHeight) / 2;

    pdf.addImage(imgData, "PNG", x, y, drawWidth, drawHeight);
    pdf.save(`${filename}.pdf`);
}

window.ChartExport = { downloadChartImage, downloadChartPdf };
