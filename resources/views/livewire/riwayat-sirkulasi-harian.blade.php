<div>
    <div class="sirkulasi-section">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <p class="text-gray-950 dark:text-white" style="font-size: 0.9375rem; font-weight: 600; margin: 0;">Riwayat Peminjaman & Pengembalian Hari Ini</p>
            <x-filament::badge color="gray">{{ $this->riwayatLengkapHariIni->count() }} transaksi</x-filament::badge>
        </div>

        {{-- BARU: dibatasi max-height + scroll internal - tabel ini
             sebelumnya tumbuh tanpa batas seiring transaksi hari ini
             bertambah, ikut mendorong footer di bawah halaman keluar
             viewport (gap: footer harus terlihat tanpa scroll dahulu). --}}
        <div style="overflow-x: auto; overflow-y: auto; max-height: 320px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                <thead>
                    <tr class="text-gray-500 dark:text-gray-400" style="text-align: left; border-bottom: 1px solid rgba(0,0,0,0.08);">
                        <th style="padding: 0.5rem 0.75rem; font-weight: 500;">Waktu</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 500;">Pengguna</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 500;">Buku</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 500;">Aksi</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 500;">Diproses Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->riwayatLengkapHariIni as $item)
                        <tr class="dark:border-white/10" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <td class="text-gray-500 dark:text-gray-400" style="padding: 0.5rem 0.75rem; white-space: nowrap;">{{ $item['waktu']?->format('H:i:s') }}</td>
                            <td class="text-gray-950 dark:text-white" style="padding: 0.5rem 0.75rem;">{{ $item['nama_user'] }}</td>
                            <td class="text-gray-950 dark:text-white" style="padding: 0.5rem 0.75rem;">{{ $item['judul_buku'] }}</td>
                            <td style="padding: 0.5rem 0.75rem;">
                                <x-filament::badge :color="$item['aksi'] === 'dipinjamkan' ? 'primary' : 'success'" size="sm">{{ ucfirst($item['aksi']) }}</x-filament::badge>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400" style="padding: 0.5rem 0.75rem;">{{ $item['diproses_oleh'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-gray-400 dark:text-gray-500" style="padding: 1.5rem 0.75rem; text-align: center;">Belum ada transaksi hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
