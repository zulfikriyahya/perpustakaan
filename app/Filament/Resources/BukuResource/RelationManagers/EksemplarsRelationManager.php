<?php

namespace App\Filament\Resources\BukuResource\RelationManagers;

use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\EksemplarExporter;
use App\Filament\Imports\EksemplarImporter;
use App\Models\Eksemplar;
use App\Services\LabelBarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
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
use Illuminate\Database\Eloquent\Collection;

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
                // BARU - cetak 1 label barcode langsung dari baris tabel.
                // Reuse ability 'view' Eksemplar (tidak ada permission baru,
                // Aturan poin 15 - tidak mengubah skema/otorisasi).
                Action::make('cetak_label')
                    ->label('Cetak Label')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn (Eksemplar $record) => auth()->user()?->can('view', $record) ?? false)
                    ->action(function (Eksemplar $record, LabelBarcodeService $service) {
                        $record->loadMissing('buku');
                        $labels = $service->generateData(collect([$record]));

                        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
                            ->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "label-{$record->barcode}.pdf",
                            ['Content-Type' => 'application/pdf'],
                        );
                    }),
            ])
            ->toolbarActions([
                // BARU - cetak label massal dari eksemplar terpilih, satu
                // PDF sticker sheet A4 (3 kolom per baris, lihat
                // pdf.label-barcode). Reuse ability 'viewAny' Eksemplar.
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label (Massal)')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn () => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records, LabelBarcodeService $service) {
                        $records->loadMissing('buku');
                        $labels = $service->generateData($records);

                        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
                            ->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            'label-barcode-massal-'.now()->format('Ymd-His').'.pdf',
                            ['Content-Type' => 'application/pdf'],
                        );
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
