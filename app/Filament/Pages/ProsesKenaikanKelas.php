<?php

namespace App\Filament\Pages;

use App\Enums\StatusRiwayatKelas;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use RuntimeException;

/**
 * Halaman kerja untuk memutuskan status kenaikan tiap siswa aktif di
 * satu KTP asal, lalu memanggil KenaikanKelasService::prosesKenaikan()
 * sekaligus (Aturan poin 3, DRY - tidak ada logic kalkulasi di sini).
 * Diakses lewat Action 'proses_kenaikan' di KelasTahunPelajaranResource.
 *
 * Sengaja TIDAK didaftarkan ke navigasi (excludeFromNavigation) - hanya
 * dapat diakses via URL dengan parameter ?ktp=... dari Resource.
 */
class ProsesKenaikanKelas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.proses-kenaikan-kelas';

    public ?KelasTahunPelajaran $ktp = null;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(string $ktp): void
    {
        $this->ktp = KelasTahunPelajaran::query()
            ->with(['kelas', 'tahunPelajaran', 'siswaAktif'])
            ->findOrFail($ktp);

        $this->form->fill(
            $this->ktp->siswaAktif->mapWithKeys(fn($siswa) => [
                $siswa->id => StatusRiwayatKelas::Naik->value,
            ])->toArray()
        );
    }

    public function getHeading(): string
    {
        return "Proses Kenaikan Kelas: {$this->ktp->kelas->nama} ({$this->ktp->tahunPelajaran->nama})";
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(
            $this->ktp->siswaAktif->map(
                fn($siswa) => Select::make((string) $siswa->id)
                    ->label($siswa->nama . ' (' . ($siswa->nisn ?? '-') . ')')
                    ->options([
                        StatusRiwayatKelas::Naik->value => 'Naik Kelas',
                        StatusRiwayatKelas::Tinggal->value => 'Tinggal Kelas',
                        StatusRiwayatKelas::Lulus->value => 'Lulus',
                        StatusRiwayatKelas::Keluar->value => 'Keluar',
                    ])
                    ->required()
            )->all()
        )->statePath('data');
    }

    public function proses(): void
    {
        $keputusan = $this->form->getState();

        try {
            $gagal = app(KenaikanKelasService::class)->prosesKenaikan($this->ktp, $keputusan);
        } catch (RuntimeException $e) {
            Notification::make()->danger()->title('Gagal memproses kenaikan kelas')->body($e->getMessage())->send();
            return;
        }

        if (empty($gagal)) {
            Notification::make()->success()->title('Kenaikan kelas berhasil diproses untuk semua siswa.')->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Sebagian siswa gagal diproses')
                ->body(collect($gagal)->map(fn($pesan, $nama) => "{$nama}: {$pesan}")->implode('; '))
                ->send();
        }

        $this->redirect(\App\Filament\Resources\KelasTahunPelajaranResource::getUrl());
    }
}
