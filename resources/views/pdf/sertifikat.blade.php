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
            padding: 15mm 20mm;
        }

        .bingkai {
            border: 3px solid #0f766e;
            padding: 12mm;
            text-align: center;
            height: 165mm;
        }

        .label-atas {
            font-size: 11px;
            letter-spacing: 3px;
            color: #64748b;
            text-transform: uppercase;
        }

        .judul {
            font-size: 26px;
            font-weight: 700;
            color: #134e4a;
            margin-top: 6mm;
        }

        .teks-diberikan {
            font-size: 12px;
            color: #475569;
            margin-top: 10mm;
        }

        .nama-penerima {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4mm;
            border-bottom: 1px solid #cbd5e1;
            display: inline-block;
            padding-bottom: 2mm;
        }

        .teks-atas {
            font-size: 13px;
            color: #475569;
            margin-top: 8mm;
        }

        .nama-item {
            font-size: 18px;
            font-weight: 700;
            color: #0f766e;
            margin-top: 2mm;
        }

        .deskripsi-item {
            font-size: 11px;
            color: #64748b;
            margin-top: 3mm;
            max-width: 130mm;
            margin-left: auto;
            margin-right: auto;
        }

        .footer {
            margin-top: 16mm;
            font-size: 10px;
            color: #94a3b8;
        }

        .nomor {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="bingkai">
        <div class="label-atas">Perpustakaan MTs Negeri 1 Pandeglang</div>
        <div class="judul">{{ $judulSertifikat }}</div>

        <div class="teks-diberikan">Diberikan kepada</div>
        <div class="nama-penerima">{{ $namaPenerima }}</div>

        <div class="teks-atas">atas pencapaian</div>
        <div class="nama-item">{{ $namaItem }}</div>

        @if ($deskripsiItem)
            <div class="deskripsi-item">{{ $deskripsiItem }}</div>
        @endif

        <div class="footer">
            Diterbitkan pada {{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
        </div>
        <div class="nomor">No. Sertifikat: {{ $nomorSertifikat }}</div>
    </div>
</body>
</html>
