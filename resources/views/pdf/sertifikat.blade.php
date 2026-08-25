<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judulSertifikat }}</title>
    <style>
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
            padding: 12mm 10mm;
            color: #1e293b;
        }

        /* Dua bingkai bersarang - tipis di luar, tebal di dalam, kesan
           formal. Tinggi disesuaikan untuk kanvas A4 portrait
           (297mm tinggi halaman, dikurangi padding body atas+bawah 24mm). */
        .bingkai-luar {
            border: 0.75px solid #94a3b8;
            padding: 4mm;
            height: 273mm;
        }

        .bingkai-dalam {
            border: 2px solid #0f766e;
            height: 100%;
            padding: 14mm 12mm 10mm 12mm;
            text-align: center;
            position: relative;
        }

        /* Ornamen sudut - aksen emas tipis, bukan mencolok */
        .sudut {
            position: absolute;
            width: 10mm;
            height: 10mm;
        }

        .sudut-kiri-atas   { top: -2px; left: -2px; border-top: 2px solid #b45309; border-left: 2px solid #b45309; }
        .sudut-kanan-atas  { top: -2px; right: -2px; border-top: 2px solid #b45309; border-right: 2px solid #b45309; }
        .sudut-kiri-bawah  { bottom: -2px; left: -2px; border-bottom: 2px solid #b45309; border-left: 2px solid #b45309; }
        .sudut-kanan-bawah { bottom: -2px; right: -2px; border-bottom: 2px solid #b45309; border-right: 2px solid #b45309; }

        .label-tipe {
            font-size: 10px;
            letter-spacing: 4px;
            color: #b45309;
            text-transform: uppercase;
            font-weight: 700;
        }

        .label-atas {
            font-size: 11.5px;
            letter-spacing: 1.5px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 3mm;
        }

        .garis-hias {
            width: 28mm;
            height: 1px;
            background-color: #b45309;
            margin: 5mm auto 0 auto;
        }

        .judul {
            font-size: 32px;
            font-weight: 700;
            color: #134e4a;
            margin-top: 8mm;
            letter-spacing: 0.5px;
        }

        .teks-diberikan {
            font-size: 12.5px;
            color: #475569;
            margin-top: 14mm;
            font-style: italic;
        }

        .nama-penerima {
            font-size: 27px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4mm;
            padding-bottom: 3mm;
            border-bottom: 0.75px solid #cbd5e1;
            display: inline-block;
            min-width: 120mm;
        }

        .teks-atas {
            font-size: 13px;
            color: #475569;
            margin-top: 12mm;
        }

        .nama-item {
            font-size: 20px;
            font-weight: 700;
            color: #0f766e;
            margin-top: 3mm;
        }

        .deskripsi-item {
            font-size: 11px;
            color: #64748b;
            margin-top: 4mm;
            max-width: 140mm;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Tanda tangan disusun BERTUMPUK (bukan berdampingan seperti versi
           landscape sebelumnya) - lebar portrait tidak cukup lega untuk
           2 kolom tanda tangan + kolom QR berdampingan tanpa terasa sesak. */
        .baris-ttd {
            margin-top: 18mm;
            width: 100%;
        }

        .kolom-ttd {
            display: inline-block;
            width: 60mm;
            vertical-align: top;
            text-align: center;
        }

        .ttd-kiri  { margin-right: 10mm; }
        .ttd-kanan { margin-left: 10mm; }

        .ttd-tanggal {
            font-size: 10px;
            color: #64748b;
        }

        .ttd-garis {
            margin-top: 14mm;
            border-top: 0.75px solid #94a3b8;
            padding-top: 2mm;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        .ttd-jabatan {
            font-size: 9px;
            color: #64748b;
            margin-top: 0.5mm;
        }

        /* QR verifikasi dipindah ke baris tersendiri di bawah tanda
           tangan (portrait) - sebelumnya sejajar di kanan (landscape). */
        .baris-qr {
            margin-top: 10mm;
            text-align: center;
        }

        .qr-gambar {
            width: 20mm;
            height: 20mm;
        }

        .qr-label {
            font-size: 7.5px;
            color: #94a3b8;
            margin-top: 1.5mm;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .footer-bawah {
            margin-top: 8mm;
            padding-top: 3mm;
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
    <div class="bingkai-luar">
        <div class="bingkai-dalam">
            <div class="sudut sudut-kiri-atas"></div>
            <div class="sudut sudut-kanan-atas"></div>
            <div class="sudut sudut-kiri-bawah"></div>
            <div class="sudut sudut-kanan-bawah"></div>

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

            <div class="baris-ttd">
                <div class="kolom-ttd ttd-kiri">
                    <div class="ttd-tanggal">{{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</div>
                    <div class="ttd-garis">Kepala Perpustakaan</div>
                    <div class="ttd-jabatan">MTs Negeri 1 Pandeglang</div>
                </div>

                <div class="kolom-ttd ttd-kanan">
                    <div class="ttd-tanggal">&nbsp;</div>
                    <div class="ttd-garis">Pustakawan</div>
                    <div class="ttd-jabatan">Penanggung Jawab Program</div>
                </div>
            </div>

            <div class="baris-qr">
                <img src="{{ $qrGambar }}" class="qr-gambar" alt="QR Verifikasi">
                <div class="qr-label">Pindai untuk verifikasi</div>
            </div>

            <div class="footer-bawah">
                <img src="{{ $barcodeGambar }}" class="barcode-gambar" alt="Barcode Nomor Sertifikat">
                <div class="nomor">No. Sertifikat: {{ $nomorSertifikat }}</div>
                <div class="footer-catatan">Diterbitkan secara elektronik oleh Sistem Perpustakaan</div>
            </div>
        </div>
    </div>
</body>
</html>
