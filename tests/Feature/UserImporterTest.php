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
 * Perlu didokumentasikan di README/Makefile bahwa fresh deploy WAJIB
 * `php artisan shield:generate --all` setelah migrate, bukan cukup seed.
 *
 * TODO: GAP-SPEC - 'columnMap' disertakan eksplisit di data action
 * karena pemetaan kolom CSV<->Importer normalnya diisi UI wizard step-2
 * secara reaktif setelah upload file - dalam callAction() satu langkah,
 * ini tidak ter-populate otomatis walau header CSV persis sama dengan
 * nama kolom Importer.
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

    protected function buatKtp(string $namaKelas, string $namaTahun): KelasTahunPelajaran
    {
        $jurusan = Jurusan::query()->create([
            'nama' => 'IPA',
            'kode' => 'IPA-'.uniqid(),
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
            'kelas_tahun_pelajaran' => 'kelas_tahun_pelajaran',
            'jabatan' => 'jabatan',
            'no_telepon' => 'no_telepon',
        ];
    }

    public function test_import_dengan_kelas_valid_membuat_riwayat_kelas_siswa(): void
    {
        $this->actingAsSuperAdmin();
        $ktp = $this->buatKtp('X IPA 1', '2025/2026');

        $csv = "nama,nisn,nip,kelas_tahun_pelajaran,jabatan,no_telepon\n"
            ."Budi Santoso,1001,,X IPA 1 - 2025/2026,,081234567890\n";

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
        $this->buatKtp('X IPA 1', '2025/2026');

        $csv = "nama,nisn,nip,kelas_tahun_pelajaran,jabatan,no_telepon\n"
            ."Ani Wijaya,1002,,Kelas Ngasal - Tahun Ngasal,,081234567891\n";

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

        $csv = "nama,nisn,nip,kelas_tahun_pelajaran,jabatan,no_telepon\n"
            ."Citra Dewi,1003,,,,081234567892\n";

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
}
