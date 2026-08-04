<?php

namespace App\Filament\Pages;

use App\Enums\StatusRiwayatKelas;
use App\Filament\Resources\KelasTahunPelajaranResource;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use RuntimeException;

/**
 * Halaman kerja untuk memutuskan status kenaikan tiap siswa aktif di
 * satu KTP asal, lalu memanggil KenaikanKelasService::prosesKenaikan()
 * sekaligus (Aturan poin 3, DRY - tidak ada logic kalkulasi disini).
 * Diakses lewat Action 'proses_kenaikan' di KelasTahunPelajaranResource.
 *
 * Sengaja TIDAK didaftarkan ke navigasi (excludeFromNavigation) - hanya
 * dapat diakses via URL dengan parameter route {ktp} dari Resource
 * (bukan query string - lihat $slug di bawah, wajib match dengan
 * ProsesKenaikanKelas::getUrl(['ktp' => ...]) di KelasTahunPelajaranResource).
 *
 * TODO: ASUMSI - dipakai Section + field "Set Semua" (bukan Wizard
 * bertahap) untuk mengompakkan pengisian. Alasan: ini bukan alur
 * sekuensial per tahap, melainkan satu matriks keputusan independen per
 * siswa yang disubmit sekaligus dalam satu transaksi
 * (KenaikanKelasService::prosesKenaikan()) - Wizard per siswa akan
 * memperlambat pengisian untuk kelas besar, bukan mempercepat. Jika
 * yang diinginkan tetap Wizard (mis. dikelompokkan per halaman N siswa),
 * beri tahu agar disesuaikan.
 */
class ProsesKenaikanKelas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.proses-kenaikan-kelas';

    protected static ?string $slug = 'proses-kenaikan-kelas/{ktp}';

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
            $this->ktp->siswaAktif->mapWithKeys(fn ($siswa) => [
                $siswa->id => StatusRiwayatKelas::Naik->value,
            ])->toArray()
        );
    }

    public function getHeading(): string
    {
        return "Proses Kenaikan Kelas: {$this->ktp->kelas->nama} ({$this->ktp->tahunPelajaran->nama})";
    }

    protected function opsiStatus(): array
    {
        return [
            StatusRiwayatKelas::Naik->value => 'Naik Kelas',
            StatusRiwayatKelas::Tinggal->value => 'Tinggal Kelas',
            StatusRiwayatKelas::Lulus->value => 'Lulus',
            StatusRiwayatKelas::Keluar->value => 'Keluar',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi Cepat')
                ->description('Isi semua siswa sekaligus dengan status yang sama, lalu koreksi satu-satu untuk pengecualian (mis. yang tinggal kelas/keluar) di bawah.')
                ->schema([
                    Select::make('set_semua')
                        ->label('Set Semua Siswa ke Status')
                        ->options($this->opsiStatus())
                        ->live()
                        // Non-persisten - HANYA convenience UI, tidak ikut
                        // dikirim ke KenaikanKelasService::prosesKenaikan()
                        // (Aturan poin 3 - jangan sampai key 'set_semua'
                        // ikut ditafsirkan sebagai user_id oleh service).
                        ->dehydrated(false)
                        ->afterStateUpdated(function (?string $state, callable $set) {
                            if (! $state) {
                                return;
                            }

                            foreach ($this->ktp->siswaAktif as $siswa) {
                                $set((string) $siswa->id, $state);
                            }
                        }),
                ]),

            Section::make('Keputusan per Siswa')
                ->description('Ubah individual jika berbeda dari hasil "Set Semua" di atas.')
                ->columns(2)
                ->schema(
                    $this->ktp->siswaAktif->map(
                        fn ($siswa) => Select::make((string) $siswa->id)
                            ->label($siswa->nama.' ('.($siswa->nisn ?? '-').')')
                            ->options($this->opsiStatus())
                            ->required()
                            ->validationMessages([
                                'required' => "Status kenaikan untuk {$siswa->nama} wajib dipilih.",
                            ])
                    )->all()
                ),
        ])->statePath('data');
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
                ->body(collect($gagal)->map(fn ($pesan, $nama) => "{$nama}: {$pesan}")->implode('; '))
                ->send();
        }

        $this->redirect(KelasTahunPelajaranResource::getUrl());
    }
}
