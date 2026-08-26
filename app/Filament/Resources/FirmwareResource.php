<?php

namespace App\Filament\Resources;

use App\Filament\Exports\FirmwareReleaseExporter;
use App\Filament\Resources\FirmwareResource\Pages;
use App\Models\FirmwareRelease;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FirmwareResource extends Resource
{
    protected static ?string $model = FirmwareRelease::class;

    protected static ?string $navigationLabel = 'Firmware OTA';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rilis Firmware')
                ->description('Versi wajib format semver (x.y.z), dibandingkan device via compareFirmwareVersion().')
                ->columns(2)
                ->schema([
                    TextInput::make('version')
                        ->label('Versi (semver x.y.z)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->rule('regex:/^\d+\.\d+\.\d+$/')
                        ->helperText('Contoh: 1.4.2')
                        ->validationMessages([
                            'required' => 'Versi firmware wajib diisi.',
                            'unique' => 'Versi ini sudah pernah dirilis sebelumnya.',
                            'regex' => 'Format versi harus x.y.z (mis. 1.4.2), sesuai yang dibaca device.',
                        ]),
                    Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Hanya rilis aktif dengan versi tertinggi yang ditawarkan ke device.'),
                    FileUpload::make('file')
                        ->label('File Firmware (.bin)')
                        ->disk('public')
                        ->directory('firmware')
                        ->required(fn (string $context) => $context === 'create')
                        ->columnSpanFull()
                        ->helperText('Upload ulang file setiap kali menyimpan (form Edit tidak menampilkan file lama).')
                        ->validationMessages([
                            'required' => 'File firmware (.bin) wajib diunggah.',
                        ]),
                    Textarea::make('catatan')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(FirmwareReleaseExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', FirmwareRelease::class) ?? false),
            ])
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
