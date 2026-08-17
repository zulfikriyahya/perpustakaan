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
            padding: 9mm;
            color: #1e293b;
        }

        /* Dua bingkai bersarang - tipis di luar, tebal di dalam, kesan formal */
        .bingkai-luar {
            border: 0.75px solid #94a3b8;
            padding: 3mm;
            height: 192mm;
        }

        .bingkai-dalam {
            border: 2px solid #0f766e;
            height: 100%;
            padding: 9mm 16mm 7mm 16mm;
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
            font-size: 9.5px;
            letter-spacing: 4px;
            color: #b45309;
            text-transform: uppercase;
            font-weight: 700;
        }

        .label-atas {
            font-size: 11px;
            letter-spacing: 1.5px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2.5mm;
        }

        .garis-hias {
            width: 26mm;
            height: 1px;
            background-color: #b45309;
            margin: 4mm auto 0 auto;
        }

        .judul {
            font-size: 29px;
            font-weight: 700;
            color: #134e4a;
            margin-top: 5mm;
            letter-spacing: 0.5px;
        }

        .teks-diberikan {
            font-size: 11.5px;
            color: #475569;
            margin-top: 8mm;
            font-style: italic;
        }

        .nama-penerima {
            font-size: 25px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 3.5mm;
            padding-bottom: 2.5mm;
            border-bottom: 0.75px solid #cbd5e1;
            display: inline-block;
            min-width: 110mm;
        }

        .teks-atas {
            font-size: 12px;
            color: #475569;
            margin-top: 7mm;
        }

        .nama-item {
            font-size: 18px;
            font-weight: 700;
            color: #0f766e;
            margin-top: 2mm;
        }

        .deskripsi-item {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 3mm;
            max-width: 135mm;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        /* Baris bawah: tanda tangan (kiri/tengah) + QR verifikasi (kanan) */
        .baris-bawah {
            margin-top: 11mm;
            width: 100%;
        }

        .kolom-ttd {
            display: inline-block;
            width: 55mm;
            vertical-align: top;
            text-align: center;
        }

        .ttd-kiri  { float: left; margin-left: 8mm; }
        .ttd-kanan { float: left; margin-left: 14mm; }

        .kolom-qr {
            float: right;
            margin-right: 8mm;
            width: 34mm;
            text-align: center;
        }

        .qr-gambar {
            width: 22mm;
            height: 22mm;
        }

        .qr-label {
            font-size: 7.5px;
            color: #94a3b8;
            margin-top: 1.5mm;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .ttd-tanggal {
            font-size: 9.5px;
            color: #64748b;
        }

        .ttd-garis {
            margin-top: 13mm;
            border-top: 0.75px solid #94a3b8;
            padding-top: 2mm;
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .ttd-jabatan {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 0.5mm;
        }

        .footer-bawah {
            clear: both;
            margin-top: 10mm;
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

            <div class="baris-bawah">
                <div class="kolom-qr">
                    <img src="{{ $qrGambar }}" class="qr-gambar" alt="QR Verifikasi">
                    <div class="qr-label">Pindai untuk verifikasi</div>
                </div>

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

            <div class="footer-bawah">
                <img src="{{ $barcodeGambar }}" class="barcode-gambar" alt="Barcode Nomor Sertifikat">
                <div class="nomor">No. Sertifikat: {{ $nomorSertifikat }}</div>
                <div class="footer-catatan">Diterbitkan secara elektronik oleh Sistem Perpustakaan</div>
            </div>
        </div>
    </div>
</body>
</html>
