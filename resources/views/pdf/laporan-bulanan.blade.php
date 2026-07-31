<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 0; }
        h2 { font-size: 13px; margin-top: 24px; margin-bottom: 6px; border-bottom: 1px solid #999; padding-bottom: 4px; }
        .subheading { color: #555; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
        .ringkasan-box { margin-bottom: 8px; }
        .ringkasan-box span { display: inline-block; margin-right: 16px; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    <h1>Laporan Bulanan Perpustakaan</h1>
    <p class="subheading">Periode: {{ $periode_label }}</p>

    {{-- PEMINJAMAN --}}
    <div class="section">
        <h2>Peminjaman</h2>
        <div class="ringkasan-box">
            <span><strong>Total:</strong> {{ $peminjaman['total'] }}</span>
            @foreach ($peminjaman['per_status'] as $status => $jumlah)
                <span><strong>{{ ucfirst($status) }}:</strong> {{ $jumlah }}</span>
            @endforeach
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Pinjam</th>
                    <th>Peminjam</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjaman['detail'] as $p)
                    <tr>
                        <td>{{ $p->tanggal_pinjam->format('d-m-Y') }}</td>
                        <td>{{ $p->user->nama }}</td>
                        <td>{{ $p->buku->judul }}</td>
                        <td>{{ $p->tanggal_jatuh_tempo->format('d-m-Y') }}</td>
                        <td>{{ ucfirst($p->status->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PENGEMBALIAN --}}
    <div class="section">
        <h2>Pengembalian</h2>
        <div class="ringkasan-box">
            <span><strong>Total:</strong> {{ $pengembalian['total'] }}</span>
            @foreach ($pengembalian['per_kondisi'] as $kondisi => $jumlah)
                <span><strong>{{ ucfirst($kondisi) }}:</strong> {{ $jumlah }}</span>
            @endforeach
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Kembali</th>
                    <th>Peminjam</th>
                    <th>Buku</th>
                    <th>Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengembalian['detail'] as $p)
                    <tr>
                        <td>{{ $p->tanggal_kembali->format('d-m-Y') }}</td>
                        <td>{{ $p->peminjaman->user->nama }}</td>
                        <td>{{ $p->peminjaman->buku->judul }}</td>
                        <td>{{ ucfirst($p->kondisi->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- DENDA --}}
    <div class="section">
        <h2>Denda</h2>
        <div class="ringkasan-box">
            <span><strong>Total Transaksi:</strong> {{ $denda['total'] }}</span>
            <span><strong>Total Nominal:</strong> Rp {{ number_format($denda['total_nominal'], 0, ',', '.') }}</span>
            <span><strong>Sudah Lunas:</strong> Rp {{ number_format($denda['total_nominal_lunas'], 0, ',', '.') }}</span>
            <span><strong>Belum Lunas:</strong> Rp {{ number_format($denda['total_nominal_belum_lunas'], 0, ',', '.') }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Tipe</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($denda['detail'] as $d)
                    <tr>
                        <td>{{ $d->created_at->format('d-m-Y') }}</td>
                        <td>{{ $d->user->nama }}</td>
                        <td>{{ ucfirst($d->tipe->value) }}</td>
                        <td>Rp {{ number_format($d->nominal, 0, ',', '.') }}</td>
                        <td>{{ $d->status_lunas ? 'Lunas' : 'Belum Lunas' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- KUNJUNGAN --}}
    <div class="section">
        <h2>Kunjungan</h2>
        <div class="ringkasan-box">
            <span><strong>Total Kunjungan:</strong> {{ $kunjungan['total'] }}</span>
            <span><strong>Pengunjung Unik:</strong> {{ $kunjungan['user_unik'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Pengunjung</th>
                    <th>Sumber</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kunjungan['detail'] as $k)
                    <tr>
                        <td>{{ $k->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $k->jam_tap }}</td>
                        <td>{{ $k->user->nama }}</td>
                        <td>{{ ucfirst($k->source->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- POINT --}}
    <div class="section">
        <h2>Point</h2>
        <div class="ringkasan-box">
            <span><strong>Total Transaksi:</strong> {{ $point['total_transaksi'] }}</span>
            <span><strong>Total Nilai:</strong> {{ $point['total_nilai'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Nilai</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($point['detail'] as $p)
                    <tr>
                        <td>{{ $p->created_at->format('d-m-Y') }}</td>
                        <td>{{ $p->user->nama }}</td>
                        <td>{{ ucfirst($p->event_type->value) }}</td>
                        <td>{{ $p->nilai }}</td>
                        <td>{{ $p->keterangan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
