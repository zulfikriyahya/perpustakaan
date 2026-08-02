<?php

namespace Tests\Feature;

use App\Enums\RoleUser;
use App\Enums\StatusRiwayatKelas;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * TODO: GAP-SPEC (operasional, bukan bug kode) - ShieldSeeder TIDAK
 * membuat permission Resource (mis. ViewAny:User) - itu hanya dihasilkan
 * oleh command `php artisan shield:generate` yang harus dijalankan manual
 * di setiap environment baru (dev/staging/production/CI), terpisah dari
 * `php artisan db:seed`. Test ini mensimulasikan permission User secara
 * eksplisit karena tidak menjalankan shield:generate penuh (mahal/interaktif).
 *
 * BUG FIX (ditemukan iterasi ini): sebelumnya test ini memakai kolom CSV
 * gabungan 'kelas_tahun_pelajaran' - TIDAK ADA di UserImporter::getColumns()
 * saat ini (kontrak sudah berubah menjadi 3 kolom terpisah: kelas_nama,
 * jurusan_kode, tahun_pelajaran_nama - lihat docblock "PERUBAHAN KONTRAK
 * (dikonfirmasi)" di UserImporter). Test lama tidak sinkron dan berisiko
 * memberi hasil palsu (mapping ke kolom yang tidak ada). Diperbaiki agar
 * cocok dengan kontrak importer yang berlaku sekarang.
 */
class UserImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShieldSeeder::class);

        Permission::firstOrCreate(['name' => 'ViewAny:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create:User', 'guard_name' => 'web']);

        Role::findByName('super_admin', 'web')->syncPermissions(Permission::all());
    }

    protected function actingAsSuperAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => RoleUser::Admin,
            'no_telepon' => '0800000000',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    protected function buatKtp(string $namaKelas, string $namaTahun, ?string $kodeJurusan = null): KelasTahunPelajaran
    {
        $jurusan = Jurusan::query()->create([
            'nama' => 'IPA',
            'kode' => $kodeJurusan ?? 'IPA-'.uniqid(),
        ]);

        $kelas = Kelas::query()->create([
            'nama' => $namaKelas,
            'tingkat' => 10,
            'jurusan_id' => $jurusan->id,
        ]);

        $tahun = TahunPelajaran::query()->create([
            'nama' => $namaTahun,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(10)->toDateString(),
            'aktif' => true,
        ]);

        return KelasTahunPelajaran::query()->create([
            'kelas_id' => $kelas->id,
            'tahun_pelajaran_id' => $tahun->id,
        ]);
    }

    protected function columnMap(): array
    {
        return [
            'nama' => 'nama',
            'nisn' => 'nisn',
            'nip' => 'nip',
            'kelas_nama' => 'kelas_nama',
            'jurusan_kode' => 'jurusan_kode',
            'tahun_pelajaran_nama' => 'tahun_pelajaran_nama',
            'jabatan' => 'jabatan',
            'no_telepon' => 'no_telepon',
        ];
    }

    public function test_import_dengan_kelas_valid_membuat_riwayat_kelas_siswa(): void
    {
        $this->actingAsSuperAdmin();
        $ktp = $this->buatKtp('X IPA 1', '2026/2027', 'IPA');

        $csv = "nama,nisn,nip,kelas_nama,jurusan_kode,tahun_pelajaran_nama,jabatan,no_telepon\n"
            ."Budi Santoso,1001,,X IPA 1,IPA,2026/2027,,081234567890\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('import')->table(), [
                'file' => $file,
                'columnMap' => $this->columnMap(),
            ])
            ->assertHasNoActionErrors();

        dump(FailedImportRow::query()->get()->toArray());
        dump(Import::query()->latest()->first()?->only(['total_rows', 'successful_rows']));

        $user = User::query()->where('nisn', '1001')->firstOrFail();

        $this->assertEquals($ktp->id, $user->kelas_tahun_pelajaran_id);

        $this->assertDatabaseHas('riwayat_kelas_siswas', [
            'user_id' => $user->id,
            'kelas_tahun_pelajaran_id' => $ktp->id,
            'status' => StatusRiwayatKelas::Aktif->value,
        ]);
    }

    public function test_import_dengan_kelas_tidak_ditemukan_baris_gagal_dan_user_tidak_dibuat(): void
    {
        $this->actingAsSuperAdmin();
        $this->buatKtp('X IPA 1', '2026/2027', 'IPA');

        $csv = "nama,nisn,nip,kelas_nama,jurusan_kode,tahun_pelajaran_nama,jabatan,no_telepon\n"
            ."Ani Wijaya,1002,,Kelas Ngasal,IPA,Tahun Ngasal,,081234567891\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('import')->table(), [
                'file' => $file,
                'columnMap' => $this->columnMap(),
            ]);

        $this->assertDatabaseMissing('users', ['nisn' => '1002']);
    }

    public function test_import_tanpa_kolom_kelas_tidak_membuat_riwayat(): void
    {
        $this->actingAsSuperAdmin();

        $csv = "nama,nisn,nip,kelas_nama,jurusan_kode,tahun_pelajaran_nama,jabatan,no_telepon\n"
            ."Citra Dewi,1003,,,,,,081234567892\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('import')->table(), [
                'file' => $file,
                'columnMap' => $this->columnMap(),
            ])
            ->assertHasNoActionErrors();

        $user = User::query()->where('nisn', '1003')->firstOrFail();

        $this->assertNull($user->kelas_tahun_pelajaran_id);
        $this->assertDatabaseMissing('riwayat_kelas_siswas', ['user_id' => $user->id]);
    }

    public function test_import_dengan_kelas_diisi_tanpa_jurusan_kode_baris_gagal(): void
    {
        $this->actingAsSuperAdmin();

        $csv = "nama,nisn,nip,kelas_nama,jurusan_kode,tahun_pelajaran_nama,jabatan,no_telepon\n"
            ."Doni Pratama,1004,,X IPA 1,,2026/2027,,081234567893\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('import')->table(), [
                'file' => $file,
                'columnMap' => $this->columnMap(),
            ]);

        $this->assertDatabaseMissing('users', ['nisn' => '1004']);
    }
}
