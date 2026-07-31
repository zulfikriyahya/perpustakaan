<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan Perpustakaan</title>
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
            font-size: 11px;
            color: #111;
            margin: 10px 20px;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 0;
            text-align: center;
            text-transform: uppercase;
        }

        h2 {
            font-size: 13px;
            margin-top: 24px;
            margin-bottom: 6px;
            padding: 6px 8px;
            background-color: #D0F0C0;
            font-weight: 700;
        }

        .subheading {
            color: #555;
            text-align: center;
            margin-top: 2px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #D0F0C0;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background: #D0F0C0;
            font-weight: 700;
        }

        .ringkasan-box {
            margin-bottom: 8px;
            background-color: #f9f9f9;
            border: 1px solid #D0F0C0;
            padding: 6px 8px;
        }

        .ringkasan-box span {
            display: inline-block;
            margin-right: 16px;
        }

        .section {
            page-break-after: always;
        }

        .section:last-child {
            page-break-after: auto;
        }

        .badge-list, .reward-list, .punishment-list {
            margin: 0;
            padding-left: 14px;
        }

        .badge-list li, .reward-list li, .punishment-list li {
            margin-bottom: 2px;
        }
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
                        <td>{{ $p->eksemplar->buku->judul }}</td>
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
                        <td>{{ $p->peminjaman->eksemplar->buku->judul }}</td>
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

    {{-- BADGE, REWARD, PUNISHMENT --}}
    <div class="section">
        <h2>User Pemilik Badge, Reward &amp; Punishment</h2>
        <div class="ringkasan-box">
            <span><strong>Total Badge Baru:</strong> {{ $poin_reward_punishment['total_badge'] }}</span>
            <span><strong>Total Reward Didapat:</strong> {{ $poin_reward_punishment['total_reward'] }}</span>
            <span><strong>Total Punishment Diterapkan:</strong> {{ $poin_reward_punishment['total_punishment'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 18%;">User</th>
                    <th style="width: 27%;">Riwayat Badge</th>
                    <th style="width: 27%;">Riwayat Reward</th>
                    <th style="width: 28%;">Riwayat Punishment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($poin_reward_punishment['per_user'] as $userId => $data)
                    <tr>
                        <td>{{ $data['nama'] }}</td>
                        <td>
                            @if ($data['badge']->isEmpty())
                                -
                            @else
                                <ul class="badge-list">
                                    @foreach ($data['badge'] as $b)
                                        <li>{{ $b->levelBadge->nama_badge }} ({{ $b->tanggal_didapat->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            @if ($data['reward']->isEmpty())
                                -
                            @else
                                <ul class="reward-list">
                                    @foreach ($data['reward'] as $r)
                                        <li>{{ $r->reward->nama }} ({{ $r->tanggal_didapat->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            @if ($data['punishment']->isEmpty())
                                -
                            @else
                                <ul class="punishment-list">
                                    @foreach ($data['punishment'] as $pl)
                                        <li>{{ $pl->punishment->nama }} ({{ $pl->tanggal_diterapkan->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data badge/reward/punishment bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
