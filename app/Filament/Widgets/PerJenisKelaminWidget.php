<?php

namespace App\Filament\Widgets;

use App\Enums\JenisKelamin;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Filament\Widgets\ChartWidget;

/**
 * Perbandingan Laki-laki vs Perempuan untuk Kunjungan, Peminjaman,
 * Pengembalian - tahun berjalan. Pengembalian tidak punya user_id
 * langsung, resolusi lewat relasi peminjaman.user (Aturan poin 3 - tetap
 * pakai relasi Model yang sudah ada, tidak duplikasi join manual di raw
 * SQL).
 *
 * TODO: GAP-SPEC - user dengan jenis_kelamin masih null (data lama,
 * belum diisi) TIDAK ikut dihitung di kedua batang L/P - jumlah total
 * chart ini bisa lebih kecil dari total kunjungan/peminjaman
 * sebenarnya selama data belum lengkap.
 */
class PerJenisKelaminWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $maxHeight = '500px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Kunjungan, Peminjaman & Pengembalian per Jenis Kelamin ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $hitungKunjungan = fn (JenisKelamin $jk) => Kunjungan::query()
            ->whereYear('tanggal', $tahun)
            ->whereHas('user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        $hitungPeminjaman = fn (JenisKelamin $jk) => Peminjaman::query()
            ->whereYear('tanggal_pinjam', $tahun)
            ->whereHas('user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        $hitungPengembalian = fn (JenisKelamin $jk) => Pengembalian::query()
            ->whereYear('tanggal_kembali', $tahun)
            ->whereHas('peminjaman.user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Laki-laki',
                    'data' => [
                        $hitungKunjungan(JenisKelamin::LakiLaki),
                        $hitungPeminjaman(JenisKelamin::LakiLaki),
                        $hitungPengembalian(JenisKelamin::LakiLaki),
                    ],
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Perempuan',
                    'data' => [
                        $hitungKunjungan(JenisKelamin::Perempuan),
                        $hitungPeminjaman(JenisKelamin::Perempuan),
                        $hitungPengembalian(JenisKelamin::Perempuan),
                    ],
                    'backgroundColor' => '#ec4899',
                ],
            ],
            'labels' => ['Kunjungan', 'Peminjaman', 'Pengembalian'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
