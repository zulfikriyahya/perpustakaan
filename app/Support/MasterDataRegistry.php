<?php

namespace App\Support;

use App\Enums\RoleUser;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\FirmwareRelease;
use App\Models\Jurusan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\Kunjungan;
use App\Models\LevelBadge;
use App\Models\LevelBadgeLog;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Punishment;
use App\Models\PunishmentLog;
use App\Models\Rak;
use App\Models\Reward;
use App\Models\RewardLog;
use App\Models\RiwayatKelasSiswa;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Rules\FormatKartuRfid;
use App\Services\BukuImportResolverService;
use App\Services\KenaikanKelasService;
use App\Services\UserImportResolverService;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Facades\Validator;

class MasterDataRegistry
{
    public static function items(): array
    {
        return [
            [
                'key' => 'jurusan',
                'label' => 'Jurusan',
                'model' => Jurusan::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'kode' => fn ($r) => $r->kode,
                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || empty($row['kode'])) {
                        throw new RowImportFailedException('Nama dan kode jurusan wajib diisi.');
                    }
                    Jurusan::query()->updateOrCreate(['kode' => $row['kode']], ['nama' => $row['nama']]);
                },
            ],
            [
                'key' => 'tahun_pelajaran',
                'label' => 'TahunPelajaran',
                'model' => TahunPelajaran::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'tanggal_mulai' => fn ($r) => $r->tanggal_mulai,
                    'tanggal_selesai' => fn ($r) => $r->tanggal_selesai,
                    'aktif' => fn ($r) => $r->aktif ? 'ya' : 'tidak',
                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || empty($row['tanggal_mulai']) || empty($row['tanggal_selesai'])) {
                        throw new RowImportFailedException('Nama, tanggal mulai, dan tanggal selesai wajib diisi.');
                    }
                    TahunPelajaran::query()->updateOrCreate(
                        ['nama' => $row['nama']],
                        [
                            'tanggal_mulai' => $row['tanggal_mulai'],
                            'tanggal_selesai' => $row['tanggal_selesai'],
                            // aktif SENGAJA tidak diubah lewat import massal -
                            // status aktif hanya lewat Action "Jadikan Aktif".
                        ]
                    );
                },
            ],
            [
                'key' => 'kelas',
                'label' => 'Kelas',
                'model' => Kelas::class,
                'importable' => true,
                'eager' => ['jurusan'],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'tingkat' => fn ($r) => $r->tingkat,
                    'jurusan' => fn ($r) => $r->jurusan?->nama,
                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || empty($row['tingkat']) || empty($row['jurusan'])) {
                        throw new RowImportFailedException('Nama, tingkat, dan jurusan wajib diisi.');
                    }
                    $jurusan = Jurusan::query()->where('nama', trim($row['jurusan']))->first();
                    if (! $jurusan) {
                        throw new RowImportFailedException("Jurusan '{$row['jurusan']}' tidak ditemukan - pastikan sheet Jurusan sudah diproses/ada di Master Data.");
                    }
                    Kelas::query()->updateOrCreate(
                        ['nama' => trim($row['nama']), 'jurusan_id' => $jurusan->id],
                        ['tingkat' => (int) $row['tingkat']]
                    );
                },
            ],
            [
                'key' => 'kelas_tahun_pelajaran',
                'label' => 'KelasTahunPelajaran',
                'model' => KelasTahunPelajaran::class,
                'importable' => true,
                'eager' => ['kelas', 'tahunPelajaran', 'waliKelas'],
                'columns' => [
                    'kelas' => fn ($r) => $r->kelas?->nama,
                    'tahun_pelajaran' => fn ($r) => $r->tahunPelajaran?->nama,
                    'wali_kelas_nip' => fn ($r) => $r->waliKelas?->nip,
                ],
                'import' => function (array $row) {
                    if (empty($row['kelas']) || empty($row['tahun_pelajaran'])) {
                        throw new RowImportFailedException('Kelas dan Tahun Pelajaran wajib diisi.');
                    }
                    $kelas = Kelas::query()->where('nama', trim($row['kelas']))->first();
                    $tahun = TahunPelajaran::query()->where('nama', trim($row['tahun_pelajaran']))->first();
                    if (! $kelas || ! $tahun) {
                        throw new RowImportFailedException('Kelas atau Tahun Pelajaran tidak ditemukan.');
                    }
                    $waliKelasId = null;
                    if (! empty($row['wali_kelas_nip'])) {
                        $wali = User::query()->where('nip', trim($row['wali_kelas_nip']))->first();
                        if (! $wali) {
                            throw new RowImportFailedException("NIP wali kelas \"{$row['wali_kelas_nip']}\" tidak ditemukan.");
                        }
                        if (! in_array($wali->role->value, ['pustakawan', 'pegawai'], true)) {
                            throw new RowImportFailedException('User dengan NIP tersebut bukan Pustakawan/Pegawai - hanya kedua role ini yang bisa dijadikan wali kelas.');
                        }
                        $waliKelasId = $wali->id;
                    }
                    KelasTahunPelajaran::query()->updateOrCreate(
                        ['kelas_id' => $kelas->id, 'tahun_pelajaran_id' => $tahun->id],
                        ['wali_kelas_id' => $waliKelasId]
                    );
                },
            ],
            [
                'key' => 'rak',
                'label' => 'Rak',
                'model' => Rak::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'lokasi' => fn ($r) => $r->lokasi,
                ],
                'import' => function (array $row) {
                    if (empty($row['nama'])) {
                        throw new RowImportFailedException('Nama rak wajib diisi.');
                    }
                    Rak::query()->updateOrCreate(['nama' => trim($row['nama'])], ['lokasi' => $row['lokasi'] ?? null]);
                },
            ],
            [
                'key' => 'kategori',
                'label' => 'Kategori',
                'model' => Kategori::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'deskripsi' => fn ($r) => $r->deskripsi,
                ],
                'import' => function (array $row) {
                    if (empty($row['nama'])) {
                        throw new RowImportFailedException('Nama kategori wajib diisi.');
                    }
                    Kategori::query()->updateOrCreate(['nama' => trim($row['nama'])], ['deskripsi' => $row['deskripsi'] ?? null]);
                },
            ],
            [
                'key' => 'level_badge',
                'label' => 'LevelBadge',
                'model' => LevelBadge::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama_badge' => fn ($r) => $r->nama_badge,
                    'min_point' => fn ($r) => $r->min_point,
                    'max_point' => fn ($r) => $r->max_point,
                    'urutan' => fn ($r) => $r->urutan,
                ],
                'import' => function (array $row) {
                    if (empty($row['nama_badge']) || ! isset($row['min_point'])) {
                        throw new RowImportFailedException('Nama badge dan min_point wajib diisi.');
                    }
                    LevelBadge::query()->updateOrCreate(
                        ['nama_badge' => trim($row['nama_badge'])],
                        [
                            'min_point' => (int) $row['min_point'],
                            'max_point' => isset($row['max_point']) && $row['max_point'] !== '' ? (int) $row['max_point'] : null,
                            'urutan' => (int) ($row['urutan'] ?? 0),
                        ]
                    );
                },
            ],
            [
                'key' => 'reward',
                'label' => 'Reward',
                'model' => Reward::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'threshold_point' => fn ($r) => $r->threshold_point,
                    'aktif' => fn ($r) => $r->aktif ? 'ya' : 'tidak',
                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || empty($row['threshold_point'])) {
                        throw new RowImportFailedException('Nama dan threshold_point wajib diisi.');
                    }
                    Reward::query()->updateOrCreate(
                        ['nama' => trim($row['nama'])],
                        [
                            'threshold_point' => (int) $row['threshold_point'],
                            'aktif' => in_array(mb_strtolower((string) ($row['aktif'] ?? 'ya')), ['ya', '1', 'true'], true),
                        ]
                    );
                },
            ],
            [
                'key' => 'punishment',
                'label' => 'Punishment',
                'model' => Punishment::class,
                'importable' => true,
                'eager' => [],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'threshold_point_minus' => fn ($r) => $r->threshold_point_minus,
                    'durasi_suspend_hari' => fn ($r) => $r->durasi_suspend_hari,
                    'aktif' => fn ($r) => $r->aktif ? 'ya' : 'tidak',
                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || ! isset($row['threshold_point_minus'])) {
                        throw new RowImportFailedException('Nama dan threshold_point_minus wajib diisi.');
                    }
                    Punishment::query()->updateOrCreate(
                        ['nama' => trim($row['nama'])],
                        [
                            'threshold_point_minus' => (int) $row['threshold_point_minus'],
                            'durasi_suspend_hari' => isset($row['durasi_suspend_hari']) && $row['durasi_suspend_hari'] !== '' ? (int) $row['durasi_suspend_hari'] : null,
                            'aktif' => in_array(mb_strtolower((string) ($row['aktif'] ?? 'ya')), ['ya', '1', 'true'], true),
                        ]
                    );
                },
            ],
            [
                'key' => 'user',
                'label' => 'User',
                'model' => User::class,
                'importable' => true,
                'eager' => ['kelasTahunPelajaran.kelas.jurusan', 'kelasTahunPelajaran.tahunPelajaran'],
                'columns' => [
                    'nama' => fn ($r) => $r->nama,
                    'role' => fn ($r) => $r->role?->value,
                    'jenis_kelamin' => fn ($r) => $r->jenis_kelamin?->value,
                    'nisn' => fn ($r) => $r->nisn,
                    'nip' => fn ($r) => $r->nip,
                    'no_telepon' => fn ($r) => $r->no_telepon,
                    'no_kartu_rfid' => fn ($r) => $r->no_kartu_rfid,
                    'avatar' => fn ($r) => $r->avatar,
                    'kelas' => fn ($r) => $r->kelasTahunPelajaran?->kelas?->nama,
                    'jurusan_kode' => fn ($r) => $r->kelasTahunPelajaran?->kelas?->jurusan?->kode,
                    'tahun_pelajaran' => fn ($r) => $r->kelasTahunPelajaran?->tahunPelajaran?->nama,

                ],
                'import' => function (array $row) {
                    if (empty($row['nama']) || empty($row['role']) || empty($row['no_telepon'])) {
                        throw new RowImportFailedException('Nama, role, dan no_telepon wajib diisi.');
                    }

                    $role = RoleUser::tryFrom(trim($row['role']));
                    if (! $role) {
                        throw new RowImportFailedException("Role \"{$row['role']}\" tidak valid. Gunakan salah satu: ".implode(', ', array_column(RoleUser::cases(), 'value')));
                    }

                    $identitas = $role === RoleUser::Siswa
                        ? trim((string) ($row['nisn'] ?? ''))
                        : trim((string) ($row['nip'] ?? ''));

                    if ($identitas === '') {
                        throw new RowImportFailedException($role === RoleUser::Siswa
                            ? 'NISN wajib diisi untuk role siswa.'
                            : 'NIP wajib diisi untuk role selain siswa.');
                    }

                    if (! empty($row['no_kartu_rfid'])) {
                        $validator = Validator::make(['no_kartu_rfid' => $row['no_kartu_rfid']], ['no_kartu_rfid' => [new FormatKartuRfid]]);
                        if ($validator->fails()) {
                            throw new RowImportFailedException('Format No. Kartu RFID tidak valid (harus 10 digit angka).');
                        }
                    }

                    $user = $role === RoleUser::Siswa
                        ? User::query()->firstOrNew(['nisn' => $identitas])
                        : User::query()->firstOrNew(['nip' => $identitas]);

                    $resolver = app(UserImportResolverService::class);

                    $ktp = null;
                    $namaKelas = trim((string) ($row['kelas'] ?? ''));
                    if ($namaKelas !== '') {
                        if ($role !== RoleUser::Siswa) {
                            throw new RowImportFailedException('Kolom kelas hanya berlaku untuk role siswa.');
                        }
                        $ktp = $resolver->resolveKtp(
                            $namaKelas,
                            trim((string) ($row['jurusan_kode'] ?? '')),
                            trim((string) ($row['tahun_pelajaran'] ?? '')),
                        );
                    }

                    $user->fill([
                        'nama' => $row['nama'],
                        'role' => $role,
                        'jenis_kelamin' => $row['jenis_kelamin'] ?: $user->jenis_kelamin,
                        'no_telepon' => $row['no_telepon'],
                        'nisn' => $role === RoleUser::Siswa ? $identitas : null,
                        'nip' => $role !== RoleUser::Siswa ? $identitas : null,
                    ]);

                    $resolver->resolvePassword($user, $row['password'] ?? null);
                    $resolver->resolveAvatar($user, $row['avatar'] ?? null, $identitas);
                    $kartuDihapus = $resolver->resolveKartuRfid($user, $row['no_kartu_rfid'] ?? null);

                    $user->save();

                    if ($ktp) {
                        app(KenaikanKelasService::class)->assignKelas($user, $ktp);
                    }

                    return $kartuDihapus ? ['kartu_dihapus' => 1] : [];
                },
            ],
            [
                'key' => 'buku',
                'label' => 'Buku',
                'model' => Buku::class,
                'importable' => true,
                'eager' => ['eksemplars.rak', 'kategoris'],
                'columns' => [
                    'judul' => fn ($r) => $r->judul,
                    'penulis' => fn ($r) => $r->penulis,
                    'isbn' => fn ($r) => $r->isbn,
                    'harga_ganti' => fn ($r) => $r->harga_ganti,
                    'stok' => fn ($r) => $r->eksemplars->count(),
                    'rak' => fn ($r) => $r->eksemplars->pluck('rak.nama')->filter()->unique()->implode('; '),
                    'kategori' => fn ($r) => $r->kategoris->pluck('nama')->implode('; '),
                ],

                'import' => function (array $row) {
                    if (empty($row['judul']) || ! isset($row['harga_ganti'])) {
                        throw new RowImportFailedException('Judul dan harga_ganti wajib diisi.');
                    }

                    $resolver = app(BukuImportResolverService::class);

                    $buku = $resolver->resolveOrCreateBuku($row['isbn'] ?? null);
                    $buku->fill([
                        'judul' => $row['judul'],
                        'penulis' => $row['penulis'] ?? null,
                        'isbn' => $row['isbn'] ?? null,
                        'harga_ganti' => (float) $row['harga_ganti'],
                    ])->save();

                    $kategoriIds = $resolver->resolveKategoriIds($row['kategori'] ?? null);
                    $resolver->syncKategori($buku, $kategoriIds);

                    $resolver->sinkronEksemplarDariSelisihStok(
                        $buku,
                        (int) ($row['stok'] ?? 0),
                        $row['rak'] ?? null,
                    );
                },
            ],

            // --- READ-ONLY (export saja, TIDAK diproses saat import) ---
            ['key' => 'denda', 'label' => 'Denda', 'model' => Denda::class, 'importable' => false, 'eager' => ['user'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'tipe' => fn ($r) => $r->tipe?->value,
                'nominal' => fn ($r) => $r->nominal,
                'status_lunas' => fn ($r) => $r->status_lunas ? 'lunas' : 'belum lunas',
            ]],
            ['key' => 'peminjaman', 'label' => 'Peminjaman', 'model' => Peminjaman::class, 'importable' => false, 'eager' => ['user', 'eksemplar.buku'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'buku' => fn ($r) => $r->eksemplar?->buku?->judul,
                'tanggal_pinjam' => fn ($r) => $r->tanggal_pinjam,
                'status' => fn ($r) => $r->status?->value,
            ]],
            ['key' => 'pengembalian', 'label' => 'Pengembalian', 'model' => Pengembalian::class, 'importable' => false, 'eager' => [], 'columns' => [
                'tanggal_kembali' => fn ($r) => $r->tanggal_kembali,
                'kondisi' => fn ($r) => $r->kondisi?->value,
            ]],
            ['key' => 'transaksi', 'label' => 'Transaksi', 'model' => Transaksi::class, 'importable' => false, 'eager' => ['user'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'jenis' => fn ($r) => $r->jenis?->value,
                'tanggal' => fn ($r) => $r->tanggal,
            ]],
            ['key' => 'kunjungan', 'label' => 'Kunjungan', 'model' => Kunjungan::class, 'importable' => false, 'eager' => ['user'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'tanggal' => fn ($r) => $r->tanggal,
            ]],
            ['key' => 'level_badge_log', 'label' => 'RiwayatBadge', 'model' => LevelBadgeLog::class, 'importable' => false, 'eager' => ['user', 'levelBadge'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'badge' => fn ($r) => $r->levelBadge?->nama_badge,
                'tanggal_didapat' => fn ($r) => $r->tanggal_didapat,
            ]],
            ['key' => 'reward_log', 'label' => 'RiwayatReward', 'model' => RewardLog::class, 'importable' => false, 'eager' => ['user', 'reward'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'reward' => fn ($r) => $r->reward?->nama,
                'tanggal_didapat' => fn ($r) => $r->tanggal_didapat,
            ]],
            ['key' => 'punishment_log', 'label' => 'RiwayatPunishment', 'model' => PunishmentLog::class, 'importable' => false, 'eager' => ['user', 'punishment'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'punishment' => fn ($r) => $r->punishment?->nama,
                'tanggal_diterapkan' => fn ($r) => $r->tanggal_diterapkan,
            ]],
            ['key' => 'riwayat_kelas_siswa', 'label' => 'RiwayatKelasSiswa', 'model' => RiwayatKelasSiswa::class, 'importable' => false, 'eager' => ['user'], 'columns' => [
                'user' => fn ($r) => $r->user?->nama,
                'status' => fn ($r) => $r->status?->value,
            ]],
            ['key' => 'firmware', 'label' => 'FirmwareOTA', 'model' => FirmwareRelease::class, 'importable' => false, 'eager' => [], 'columns' => [
                'version' => fn ($r) => $r->version,
                'aktif' => fn ($r) => $r->aktif ? 'ya' : 'tidak',
            ]],
        ];
    }
}
