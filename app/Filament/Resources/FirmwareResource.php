<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FirmwareResource\Pages;
use App\Models\FirmwareRelease;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kelola rilis firmware OTA untuk device Attendance Machine (ESP32-C3).
 * File .bin disimpan di disk 'public' (dikonfirmasi user) - URL hasil
 * upload langsung dipakai sebagai field 'url' yang dikirim ke device lewat
 * PerpustakaanDeviceController::firmwareCheck().
 *
 * TODO: GAP-SPEC - form Edit TIDAK menampilkan preview file lama (field
 * 'file' hanya dipetakan satu arah saat create/update baru), karena kolom
 * tersimpan adalah 'url' (full URL) bukan path relatif disk. Jika ingin
 * ganti versi, admin wajib upload ulang file setiap kali submit form Edit.
 */
class FirmwareResource extends Resource
{
    protected static ?string $model = FirmwareRelease::class;

    protected static ?string $navigationLabel = 'Firmware OTA';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('version')
                ->label('Versi (semver x.y.z)')
                ->required()
                ->unique(ignoreRecord: true)
                ->rule('regex:/^\d+\.\d+\.\d+$/')
                ->helperText('Format wajib x.y.z, dibandingkan device via compareFirmwareVersion().'),
            FileUpload::make('file')
                ->label('File Firmware (.bin)')
                ->disk('public')
                ->directory('firmware')
                ->required(fn (string $context) => $context === 'create')
                ->helperText('Upload ulang file setiap kali menyimpan (lihat catatan GAP-SPEC di kode).'),
            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true)
                ->helperText('Hanya rilis aktif dengan versi tertinggi yang ditawarkan ke device.'),
            Textarea::make('catatan')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->copyable(),
                TextColumn::make('md5')
                    ->label('MD5')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFirmwareReleases::route('/'),
            'create' => Pages\CreateFirmwareRelease::route('/create'),
            'edit' => Pages\EditFirmwareRelease::route('/{record}/edit'),
        ];
    }
}
