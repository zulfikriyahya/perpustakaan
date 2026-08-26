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
use App\Rules\FormatKartuRfid;
use App\Rules\FormatNomorTelepon;
use App\Services\KenaikanKelasService;
use App\Support\NomorTeleponFormatter;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static function isTargetSuperAdmin(callable $get): bool
    {
        return $get('role') === RoleUser::Admin->value;
    }

    public static function form(Schema $schema): Schema
    {
        $isProtected = fn (callable $get) => static::isTargetSuperAdmin($get);

        return $schema->components([
            Section::make('Informasi Akun')
                ->description('Data identitas dasar dan peran akun.')
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'Nama wajib diisi.',
                            'max' => 'Nama maksimal 255 karakter.',
                        ]),
                    Select::make('role')
                        ->options(collect(RoleUser::cases())->mapWithKeys(fn ($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))]))
                        ->required()
                        ->live()
                        ->hidden($isProtected)
                        ->validationMessages([
                            'required' => 'Peran (role) wajib dipilih.',
                        ])
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === RoleUser::Siswa->value) {
                                $set('nip', null);
                            } else {
                                $set('nisn', null);
                            }
                        }),
                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options(collect(JenisKelamin::cases())->mapWithKeys(fn ($j) => [$j->value => $j->label()]))
                        ->native(false)
                        ->hidden($isProtected),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->maxLength(255)
                        ->helperText('Kosongkan jika tidak ingin mengubah password.')
                        ->validationMessages([
                            'required' => 'Password wajib diisi saatmembuat user baru.',
                            'max' => 'Password maksimal 255 karakter.',
                        ]),
                    FileUpload::make('avatar')
                        ->image()
                        ->disk('public')
                        ->directory('user-avatar'),
                ]),

            Section::make('Identitas & Kepegawaian')
                ->description('Hanya tampil sesuai peran yang dipilih.')
                ->columns(2)
                ->hidden($isProtected)
                ->schema([
                    TextInput::make('nisn')
                        ->label('NISN')
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->visible(fn (callable $get) => $get('role') === RoleUser::Siswa->value)
                        ->dehydrated(fn (callable $get) => $get('role') === RoleUser::Siswa->value)
                        ->validationMessages([
                            'unique' => 'NISN ini sudah dipakai userlain yang masih aktif.',
                            'max' => 'NISN maksimal 255 karakter.',
                        ]),
                    TextInput::make('nip')
                        ->label('NIP')
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->visible(fn (callable $get) => $get('role') !== RoleUser::Siswa->value)
                        ->dehydrated(fn (callable $get) => $get('role') !== RoleUser::Siswa->value)
                        ->validationMessages([
                            'unique' => 'NIP ini sudah dipakai user lain yang masih aktif.',
                            'max' => 'NIP maksimal 255 karakter.',
                        ]),
                    TextInput::make('jabatan')
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->validationMessages([
                            'max' => 'Jabatan maksimal 255 karakter.',
                        ]),
                ]),

            Section::make('Kontak & Kartu RFID')
                ->columns(2)
                ->hidden($isProtected)
                ->schema([
                    TextInput::make('no_telepon')
                        ->label('No. Telepon')
                        ->required()
                        ->live(onBlur: true)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->tel()
                        ->rules([new FormatNomorTelepon])
                        ->afterStateUpdated(function (?string $state, callable $set) {
                            // Dinormalisasi SAAT BLUR (bukan tiap ketik, supaya
                            // kursor tidak lompat) - dilakukan di sini (bukan
                            // cuma di dehydrateStateUsing) supaya validasi
                            // unique() di bawah membandingkan nilai yang
                            // SUDAH ternormalisasi terhadap data yang juga
                            // ternormalisasi di DB (user baru/edit lain yang
                            // lewat path ini). Data lama yang belum pernah
                            // ternormalisasi tetap jadi celah yang diketahui
                            // (lihat TODO di bawah), bukan diam-diam diabaikan.
                            $set('no_telepon', NomorTeleponFormatter::normalisasi($state) ?? $state);
                        })
                        ->dehydrateStateUsing(fn (?string $state) => NomorTeleponFormatter::normalisasi($state) ?? $state)
                        ->helperText('Boleh diketik format apa pun (mis. +62, spasi, strip) - otomatis dinormalisasi jadi 628xxxxxxxxx saat pindah field/simpan.')
                        ->validationMessages([
                            'required' => 'No. telepon wajib diisi (dipakai untuk notifikasi WhatsApp).',
                            'unique' => 'No. telepon ini sudah dipakai user lain yang masih aktif.',
                            'max' => 'No. telepon maksimal 255 karakter.',
                        ]),
                    TextInput::make('no_kartu_rfid')
                        ->label('No. Kartu RFID')
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->rules([new FormatKartuRfid])
                        ->helperText('Harus persis 10 digit angka - sesuai kontrak firmware Attendance Machine.')
                        ->validationMessages([
                            'unique' => 'No. kartu RFID ini sudah dipakai user lain yang masih aktif.',
                            'max' => 'No. kartu RFID maksimal 255 karakter.',
                        ]),
                ]),

            Section::make('Kelas')
                ->columns(2)
                ->hidden($isProtected)
                ->schema([
                    Placeholder::make('kelas_tahun_pelajaran_id')
                        ->label('Kelas (Tahun Pelajaran)')
                        ->content(fn (?User $record) => $record?->kelasTahunPelajaran
                            ? "{$record->kelasTahunPelajaran->kelas->nama} - {$record->kelasTahunPelajaran->tahunPelajaran->nama}"
                            : 'Belum di-assign - gunakan aksi "Assign ke Kelas" di daftar User.')
                        ->visibleOn('edit'),
                    Select::make('assign_kelas_tahun_pelajaran_id')
                        ->label('Assign ke Kelas (opsional)')
                        ->options(
                            KelasTahunPelajaran::query()
                                ->with(['kelas', 'tahunPelajaran'])
                                ->get()
                                ->mapWithKeys(fn (KelasTahunPelajaran $ktp) => [
                                    $ktp->id => "{$ktp->kelas->nama}- {$ktp->tahunPelajaran->nama}",
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
                ]),
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
                                        $ktp->id => "{$ktp->kelas->nama} - {$ktp->tahunPelajaran->nama}",
                                    ])
                            )
                            ->searchable()->required()
                            ->validationMessages([
                                'required' => 'Kelas (Tahun Pelajaran) wajib dipilih.',
                            ]),
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
