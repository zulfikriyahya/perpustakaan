<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judulSertifikat }}</title>
<style>
    @page {
        size: A4 portrait;
        margin: 0;
    }

    @font-face {
        font-family: 'Lexend';
        src: url('{{ public_path('fonts/pdf/lexend-regular.woff2') }}') format('woff2');
        font-weight: 400;
    }

    @font-face {
        font-family: 'Lexend';
        src: url('{{ public_path('fonts/pdf/lexend-bold.woff2') }}') format('woff2');
        font-weight: 700;
    }

    * {
        font-family: 'Lexend', sans-serif;
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 15mm 16mm;
        color: #1e293b;
    }

    .kartu {
        border: 2px solid #0f766e;
        padding: 14mm 17mm 12mm 17mm;
        text-align: center;
        page-break-inside: avoid;
    }

    .label-tipe {
        font-size: 11.5px;
        letter-spacing: 4px;
        color: #b45309;
        text-transform: uppercase;
        font-weight: 700;
    }

    .label-atas {
        font-size: 12.5px;
        letter-spacing: 1.5px;
        color: #64748b;
        text-transform: uppercase;
        margin-top: 2.5mm;
    }

    .garis-hias {
        width: 30mm;
        height: 1px;
        background-color: #b45309;
        margin: 4mm auto 0 auto;
    }

    .judul {
        font-size: 30px;
        font-weight: 700;
        color: #134e4a;
        margin-top: 5mm;
        letter-spacing: 0.5px;
    }

    .teks-diberikan {
        font-size: 13px;
        color: #475569;
        margin-top: 7mm;
        font-style: italic;
    }

    .nama-penerima {
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 3mm;
        padding-bottom: 2.5mm;
        border-bottom: 0.75px solid #cbd5e1;
        display: inline-block;
        min-width: 125mm;
    }

    .teks-atas {
        font-size: 14px;
        color: #475569;
        margin-top: 7mm;
    }

    .nama-item {
        font-size: 20px;
        font-weight: 700;
        color: #0f766e;
        margin-top: 2.5mm;
    }

    .deskripsi-item {
        font-size: 12px;
        color: #64748b;
        margin-top: 3mm;
        max-width: 145mm;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.55;
    }

    .kalimat-penutup {
        font-size: 11px;
        color: #475569;
        margin-top: 6mm;
        font-style: italic;
        max-width: 140mm;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.5;
    }

    .baris-ttd {
        margin-top: 65mm;
        width: 100%;
    }

    .tabel-ttd {
        width: 150mm;
        margin: 0 auto;
        border-collapse: collapse;
    }

    .tabel-ttd td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 6mm;
    }

    .ttd-tanggal {
        font-size: 10.5px;
        color: #64748b;
    }

    /* ruang kosong untuk tanda tangan fisik/basah di atas nama jabatan */
    .ruang-ttd {
        height: 20mm;
    }

    .ttd-garis {
        border-top: 0.75px solid #94a3b8;
        padding-top: 2.5mm;
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
    }

    .ttd-jabatan {
        font-size: 9.5px;
        color: #64748b;
        margin-top: 0.5mm;
    }

    .baris-qr {
        margin-top: 6mm;
        text-align: center;
    }

    .qr-gambar {
        width: 17mm;
        height: 17mm;
    }

    .qr-label {
        font-size: 8px;
        color: #94a3b8;
        margin-top: 1.5mm;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .footer-bawah {
        margin-top: 5mm;
        padding-top: 2.5mm;
        border-top: 0.5px solid #e2e8f0;
        text-align: center;
    }

    .barcode-gambar {
        height: 9mm;
    }

    .nomor {
        font-size: 8.5px;
        color: #94a3b8;
        margin-top: 1mm;
        letter-spacing: 0.5px;
    }

    .footer-catatan {
        font-size: 8px;
        color: #cbd5e1;
        margin-top: 1mm;
    }
</style>
</head>
<body>
    <div class="kartu">
        <div class="label-tipe">{{ $tipeLabel ?? 'Sertifikat Resmi' }}</div>
        <div class="label-atas">Perpustakaan MTs Negeri 1 Pandeglang</div>
        <div class="garis-hias"></div>
        <div class="judul">{{ $judulSertifikat }}</div>

        <div class="teks-diberikan">Dengan bangga diberikan kepada</div>
        <div class="nama-penerima">{{ $namaPenerima }}</div>

        <div class="teks-atas">atas pencapaian</div>
        <div class="nama-item">{{ $namaItem }}</div>

        @if ($deskripsiItem)
            <div class="deskripsi-item">{{ $deskripsiItem }}</div>
        @endif

        {{-- TODO: ASUMSI - kalimat penutup belum dispesifikasikan di dokumen
        acuan ("lebih sempurna" tidak menyebut redaksi pasti). Kalimat di
        bawah adalah pernyataan keabsahan generik yang aman untuk kedua tipe
        sertifikat (Reward & Badge). Ganti teks ini jika sekolah punya
        redaksi resmi yang harus dipakai. --}}
        <div class="kalimat-penutup">
            Sertifikat ini diterbitkan secara resmi oleh Perpustakaan MTs Negeri 1 Pandeglang
            sebagai bentuk apresiasi dan pengakuan atas pencapaian yang telah diraih.
        </div>
        
        <div class="baris-ttd">
            <table class="tabel-ttd">
                <tr>
                    <td>
                        <div class="ttd-tanggal">&nbsp;</div>
                        <div class="ruang-ttd"></div>
                        <div class="ttd-garis">Kepala Perpustakaan</div>
                        <div class="ttd-jabatan">MTs Negeri 1 Pandeglang</div>
                    </td>
                    <td>
                        <div class="ttd-tanggal">Pandeglang, {{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</div>
                        <div class="ruang-ttd"></div>
                        <div class="ttd-garis">Pustakawan</div>
                        <div class="ttd-jabatan">Penanggung Jawab Program</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="baris-qr">
            <img src="{{ $qrGambar }}" class="qr-gambar" alt="QR Verifikasi">
            <div class="qr-label">Pindai untuk verifikasi</div>
        </div>

        <div class="footer-bawah">
            <img src="{{ $barcodeGambar }}" class="barcode-gambar" alt="Barcode NomorSertifikat">
            <div class="nomor">No. Sertifikat: {{ $nomorSertifikat }}</div>
            <div class="footer-catatan">Diterbitkan secara elektronik oleh Sistem Perpustakaan</div>
        </div>
    </div>
</body>
</html>
