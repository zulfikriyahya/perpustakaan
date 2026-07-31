<?php

namespace App\Filament\Resources\BukuResource\RelationManagers;

use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\EksemplarExporter;
use App\Filament\Imports\EksemplarImporter;
use App\Models\Eksemplar;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('barcode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('rak_id')
                ->label('Rak')
                ->relationship('rak', 'nama')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)]))
                ->required()
                ->default(StatusEksemplar::Tersedia->value)
                ->helperText('Ubah manual hanya untuk koreksi data - alur normal status diubah otomatis oleh PeminjamanService.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            ->headerActions([
                ImportAction::make()
                    ->importer(EksemplarImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Eksemplar::class) ?? false),
                ExportAction::make()
                    ->exporter(EksemplarExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Eksemplar::class) ?? false),
            ])
            ->columns([
                TextColumn::make('barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Tersedia => 'success',
                        StatusEksemplar::Dipinjam => 'warning',
                        StatusEksemplar::Rusak, StatusEksemplar::Hilang => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
                // Ditambahkan supaya baris Eksemplar soft-deleted terlihat
                // dan bisa direstore/force-delete dari tab ini.
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn (Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa diedit manual di sini.'
                        : null),
                DeleteAction::make()
                    ->disabled(fn (Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn (Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa dihapus.'
                        : null),
                RestoreAction::make(),
                // GAP-SPEC ditutup (konsisten dengan BukuResource): force
                // delete diizinkan, eksemplar_id di riwayat Peminjaman jadi
                // null (migration 2026_08_02_000007) - TAPI diblok kalau
                // masih ada Peminjaman Aktif/Terlambat pada eksemplar ini
                // (guard wajib, bukan pilihan bisnis).
                ForceDeleteAction::make()
                    ->action(function (Eksemplar $record) {
                        $adaPeminjamanBerjalan = $record->peminjamans()
                            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                            ->exists();

                        if ($adaPeminjamanBerjalan) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Eksemplar ini masih punya Peminjaman Aktif/Terlambat. Selesaikan/kembalikan dulu sebelum force delete.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ]);
    }
}
