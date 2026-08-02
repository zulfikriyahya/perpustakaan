<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; padding: 24px; font-family: sans-serif; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 16px; }
        .section-title { font-size: 12px; font-weight: bold; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; }
        img { max-width: 100%; height: auto; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $heading }}</h1>
    <div class="meta">Diekspor pada {{ now()->translatedFormat('d F Y H:i') }} WIB</div>

    @if (count($summary))
        <div class="section-title">Ringkasan</div>
        <table>
            <thead>
                <tr><th>Seri Data</th><th>Total</th><th>Rata-rata</th></tr>
            </thead>
            <tbody>
                @foreach ($summary as $s)
                    <tr>
                        <td>{{ $s['label'] }}</td>
                        <td>{{ number_format($s['total'], 0, ',', '.') }}</td>
                        <td>{{ number_format($s['rata_rata'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($rows))
        <div class="section-title">Data</div>
        <table>
            <thead>
                <tr>
                    @foreach (array_keys($rows[0]) as $col)
                        <th>{{ $col === 'label' ? 'Label' : $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $val)
                            <td>{{ $val }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Grafik</div>
    <img src="{{ $image }}" alt="Grafik">
</body>
</html>
