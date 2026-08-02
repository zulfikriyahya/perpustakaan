<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Denda;
use App\Models\KelasTahunPelajaran;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\KenaikanKelasService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Resource khusus super_admin (dikonfirmasi) - lihat UserPolicy dan
 * ShieldSeeder (permission User TIDAK disinkron ke role pustakawan).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * mengikuti pola BukuResource untuk Schema/Table API Filament ^5.7.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            Select::make('role')
                ->options(collect(RoleUser::cases())->mapWithKeys(fn ($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))]))
                ->required()
                ->live()
                // BARU (iterasi ini) - saat role diganti, bersihkan field
                // NISN/NIP yang jadi tidak relevan (dihitung PeminjamanService?
                // tidak - murni UI form, lihat visible()/dehydrated() di
                // nisn/nip di bawah). Mencegah nilai lama nyangkut diam-diam
                // di $record sebelum sempat disembunyikan dari tampilan.
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === RoleUser::Siswa->value) {
                        $set('nip', null);
                    } else {
                        $set('nisn', null);
                    }
                }),
            TextInput::make('nisn')
                ->label('NISN')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                ->maxLength(255)
                // BARU - NISN hanya relevan untuk Siswa, disembunyikan untuk
                // role lain. dehydrated() disamakan dengan visible() supaya
                // field yang disembunyikan tidak ikut ter-submit/tersimpan
                // (Aturan poin 3 - satu sumber kebenaran "role menentukan
                // identitas yang valid", bukan dua tempat berbeda).
                ->visible(fn (callable $get) => $get('role') === RoleUser::Siswa->value)
                ->dehydrated(fn (callable $get) => $get('role') === RoleUser::Siswa->value),
            TextInput::make('nip')
                ->label('NIP')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                ->maxLength(255)
                // BARU - NIP hanya relevan untuk Pegawai/Pustakawan/Admin,
                // disembunyikan untuk Siswa. Pola sama dengan 'nisn' di atas.
                ->visible(fn (callable $get) => $get('role') !== RoleUser::Siswa->value)
                ->dehydrated(fn (callable $get) => $get('role') !== RoleUser::Siswa->value),
            Select::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->options(collect(JenisKelamin::cases())->mapWithKeys(fn ($j) => [$j->value => $j->label()]))
                ->native(false),
            // Kolom 'kelas' (string bebas) sudah di-drop dari tabel users
            // (migration 2026_08_01_000006), diganti relasi
            // kelas_tahun_pelajaran_id. Ditampilkan read-only di sini -
            // penetapan/perubahan kelas WAJIB lewat KenaikanKelasService
            // (bulk action 'assign_kelas' di tabel bawah, atau proses
            // kenaikan kelas massal) supaya RiwayatKelasSiswa selalu
            // tercatat. Form ini sengaja TIDAK menyediakan input langsung
            // untuk field ini agar tidak ada jalur kedua yang melewati
            // service (Aturan poin 3, DRY).
            // TODO: GAP-SPEC - pada 'create', user baru dibuat tanpa KTP
            // (kelas_tahun_pelajaran_id null, status_akademik default
            // 'aktif' dari migration). Assignment awal dilakukan setelah
            // user tersimpan, lewat bulk action 'assign_kelas' di index.
            // Perlu dikonfirmasi apakah alur ini sudah sesuai ekspektasi,
            // atau dibutuhkan Select assignment langsung di form create.

            Placeholder::make('kelas_tahun_pelajaran_id')
                ->label('Kelas (Tahun Pelajaran)')
                ->content(fn (?User $record) => $record?->kelasTahunPelajaran
                    ? "{$record->kelasTahunPelajaran->kelas->nama} - {$record->kelasTahunPelajaran->tahunPelajaran->nama}"
                    : 'Belum di-assign - gunakan aksi "Assign ke Kelas" di daftar User.')
                ->visibleOn('edit'),
            // Hanya tampil saat create - field virtual (bukan kolom User),
            // dibuang & diproses lewat KenaikanKelasService::assignKelas()
            // di CreateUser::afterCreate(). Assignment setelah create
            // (bukan saat edit) tetap konsisten dengan alur bulk action
            // 'assign_kelas' yang juga selalu lewat service ini.
            Select::make('assign_kelas_tahun_pelajaran_id')
                ->label('Assign ke Kelas (opsional)')
                ->options(
                    KelasTahunPelajaran::query()
                        ->with(['kelas', 'tahunPelajaran'])
                        ->get()
                        ->mapWithKeys(fn (KelasTahunPelajaran $ktp) => [
                            $ktp->id => "{$ktp->kelas->nama} - {$ktp->tahunPelajaran->nama}",
                        ])
                )
                ->searchable()
                ->helperText('Bisa dikosongkan, assign belakangan lewat aksi "Assign ke Kelas".')
                ->dehydrated()
                ->visibleOn('create'),
            Select::make('status_akademik')
                ->options(collect(StatusAkademik::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))]))
                ->disabled()
                ->dehydrated(false)
                ->helperText('Berubah otomatis lewat proses Kenaikan Kelas / assignment, tidak bisa diedit manual di sini.')
                ->visibleOn('edit'),
            TextInput::make('jabatan')
                ->maxLength(255),
            TextInput::make('no_telepon')
                ->label('No. Telepon')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                ->maxLength(255),
            TextInput::make('no_kartu_rfid')
                ->label('No. Kartu RFID')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                ->maxLength(255),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->maxLength(255)
                ->helperText('Kosongkan jika tidak ingin mengubah password.'),
            FileUpload::make('avatar')
                ->image()
                ->disk('public')
                ->directory('user-avatar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', User::class) ?? false),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', User::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('avatar')->disk('public')->circular(),
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (RoleUser $state) => match ($state) {
                        RoleUser::Admin => 'danger',
                        RoleUser::Pustakawan => 'warning',
                        RoleUser::Pegawai => 'info',
                        RoleUser::Siswa => 'gray',
                    }),
                TextColumn::make('nisn')->label('NISN')->searchable()->toggleable(),
                TextColumn::make('nip')->label('NIP')->searchable()->toggleable(),
                TextColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas')->toggleable()->placeholder('-'),
                TextColumn::make('status_akademik')
                    ->badge()->toggleable()
                    ->color(fn (StatusAkademik $state) => match ($state) {
                        StatusAkademik::Aktif => 'success',
                        StatusAkademik::Lulus => 'info',
                        StatusAkademik::Keluar => 'gray',
                    }),
                TextColumn::make('no_telepon')->label('No. Telepon')->searchable(),
                TextColumn::make('no_kartu_rfid')->label('Kartu RFID')->searchable()->toggleable(),
                IconColumn::make('status_suspend')
                    ->label('Suspend')->boolean()
                    ->trueIcon('heroicon-o-lock-closed')->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')->falseColor('success'),
                TextColumn::make('akumulasi_point')->label('Point')->sortable(),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('role')
                    ->options(collect(RoleUser::cases())->mapWithKeys(fn ($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))])),
                SelectFilter::make('status_akademik')
                    ->options(collect(StatusAkademik::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', '', $s->value))])),
                TernaryFilter::make('status_suspend')->label('Status Suspend'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->authorize(fn (User $record) => ! $record->hasRole('super_admin')
                        && (auth()->user()?->can('delete', $record) ?? false)),
                RestoreAction::make(),
                // TODO: GAP-SPEC - guard force-delete dipilih sepihak: blokir
                // jika masih ada Peminjaman aktif/terlambat ATAU Denda belum
                // lunas milik user ini (termasuk yang sudah di-soft-delete),
                // supaya jejak keuangan/operasional tidak musnah diam-diam.
                ForceDeleteAction::make()
                    ->authorize(fn (User $record) => ! $record->hasRole('super_admin')
                        && (auth()->user()?->can('forceDelete', $record) ?? false))
                    ->action(function (User $record) {
                        $adaPeminjamanAktif = Peminjaman::query()
                            ->withTrashed()
                            ->where('user_id', $record->id)
                            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                            ->exists();
                        $adaDendaBelumLunas = Denda::query()
                            ->withTrashed()
                            ->where('user_id', $record->id)
                            ->where('status_lunas', false)
                            ->exists();

                        if ($adaPeminjamanAktif || $adaDendaBelumLunas) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('User ini masih punya Peminjaman aktif/terlambat atau Denda belum lunas.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('assign_kelas')
                    ->label('Assign ke Kelas')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Select::make('kelas_tahun_pelajaran_id')
                            ->label('Kelas (Tahun Pelajaran)')
                            ->options(
                                KelasTahunPelajaran::query()
                                    ->with(['kelas', 'tahunPelajaran'])
                                    ->get()
                                    ->mapWithKeys(fn (KelasTahunPelajaran $ktp) => [
                                        $ktp->id => "{$ktp->kelas->nama} -{$ktp->tahunPelajaran->nama}",
                                    ])
                            )
                            ->searchable()->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $ktp = KelasTahunPelajaran::query()->findOrFail($data['kelas_tahun_pelajaran_id']);
                        $service = app(KenaikanKelasService::class);
                        $records->each(fn (User $user) => $service->assignKelas($user, $ktp));

                        Notification::make()->success()->title($records->count().' user berhasil di-assign ke kelas.')->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    ->action(function (Collection $records) {
                        $dilindungi = $records->filter(fn (User $u) => $u->hasRole('super_admin'));
                        $bolehHapus = $records->reject(fn (User $u) => $u->hasRole('super_admin'));
                        $bolehHapus->each->delete();

                        if ($dilindungi->isNotEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sebagian user tidak dihapus')
                                ->body($dilindungi->count().' user dengan role super_admin dilewati.')
                                ->send();
                        }
                    })
                    ->authorize(fn () => auth()->user()?->can('deleteAny', User::class) ?? false),
            ])
            ->checkIfRecordIsSelectableUsing(fn (User $record) => ! $record->hasRole('super_admin'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
