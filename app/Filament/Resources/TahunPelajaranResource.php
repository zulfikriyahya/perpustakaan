<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TahunPelajaranResource\Pages;
use App\Models\TahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TahunPelajaranResource extends Resource
{
    protected static ?string $model = TahunPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tahun Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama (mis. 2025/2026)')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            DatePicker::make('tanggal_mulai')->required(),
            DatePicker::make('tanggal_selesai')->required()->afterOrEqual('tanggal_mulai'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tanggal_mulai')->date(),
                TextColumn::make('tanggal_selesai')->date(),
                IconColumn::make('aktif')->boolean()->label('Aktif'),
            ])
            ->recordActions([
                Action::make('jadikan_aktif')
                    ->label('Jadikan Aktif')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TahunPelajaran $record) => ! $record->aktif)
                    ->requiresConfirmation()
                    ->modalDescription('Tahun Pelajaran lain yang sedang aktif akan otomatis dinonaktifkan.')
                    ->action(function (TahunPelajaran $record) {
                        TahunPelajaran::query()->where('id', '!=', $record->id)->update(['aktif' => false]);
                        $record->update(['aktif' => true]);

                        Notification::make()->success()->title('Tahun Pelajaran diaktifkan')->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (TahunPelajaran $record) => ! $record->aktif),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTahunPelajarans::route('/'),
            'create' => Pages\CreateTahunPelajaran::route('/create'),
            'edit' => Pages\EditTahunPelajaran::route('/{record}/edit'),
        ];
    }
}
