<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label Barcode Eksemplar</title>
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
            margin: 8mm 6mm;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid td {
            width: 33.33%;
            padding: 2mm;
            vertical-align: top;
        }

        .label-box {
            border: 1px dashed #999;
            padding: 3mm;
            text-align: center;
            height: 32mm;
            overflow: hidden;
        }

        .label-box .judul {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 2mm;
            height: 10mm;
            overflow: hidden;
        }

        .label-box img {
            width: 100%;
            max-height: 12mm;
        }

        .label-box .kode-text {
            font-size: 8px;
            margin-top: 1mm;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <table class="grid">
        @foreach (array_chunk($labels, 3) as $baris)
            <tr>
                @foreach ($baris as $label)
                    <td>
                        <div class="label-box">
                            <div class="judul">{{ $label['judul'] }}</div>
                            <img src="{{ $label['gambar'] }}" alt="barcode">
                            <div class="kode-text">{{ $label['barcode'] }}</div>
                        </div>
                    </td>
                @endforeach
                @for ($i = count($baris); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
