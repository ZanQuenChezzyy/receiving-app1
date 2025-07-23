<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransmittalKembaliResource\Pages;
use App\Filament\Resources\TransmittalKembaliResource\RelationManagers;
use App\Models\TransmittalKembali;
use App\Models\TransmittalKirim;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;

class TransmittalKembaliResource extends Resource
{
    protected static ?string $model = TransmittalKembali::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengembalian')
                    ->description('Isi tanggal pengembalian dokumen dan identitas pembuat entri.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_kembali')
                                ->label('Tanggal Kembali')
                                ->placeholder('Pilih Tanggal Kembali')
                                ->native(false)
                                ->required(),

                            Select::make('created_by')
                                ->label('Dibuat oleh')
                                ->relationship('createdBy', 'name')
                                ->native(false)
                                ->disabled()
                                ->dehydrated()
                                ->default(Auth::id())
                                ->required(),
                        ]),
                    ]),

                Section::make('Daftar Transmittal Kembali')
                    ->description('Scan QR Code Transmittal untuk mengisi data pengembalian secara otomatis.')
                    ->icon('heroicon-o-qr-code')
                    ->schema([
                        Repeater::make('transmittalKembaliDetails')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('code')
                                        ->label('Scan QR Code')
                                        ->placeholder('Scan QR Dokumen')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $transmittal = TransmittalKirim::where('code', $state)->first();

                                            if (!$transmittal) {
                                                Notification::make()
                                                    ->title("QR Code tidak ditemukan.")
                                                    ->danger()
                                                    ->send();

                                                $set('code_103', null);
                                                $set('total_item', null);
                                                $set('tanggal_kirim', null);
                                                $set('transmittal_kirim_id', null);
                                                $set('do_receipt_detail_id', null);
                                                return;
                                            }

                                            // Pastikan tanggal_kirim berupa Carbon
                                            $tanggal = $transmittal->tanggal_kirim;
                                            if (!($tanggal instanceof \Carbon\Carbon)) {
                                                $tanggal = \Illuminate\Support\Carbon::parse($tanggal);
                                            }

                                            $set('code_103', $transmittal->code_103);
                                            $set('tanggal_kirim', $tanggal->format('Y-m-d'));
                                            $set('transmittal_kirim_id', $transmittal->id);
                                            $set('do_receipt_detail_id', optional($transmittal->deliveryOrderReceipts)->id);

                                            $total = $transmittal->deliveryOrderReceipts?->deliveryOrderReceiptDetails->count() ?? 0;
                                            $set('total_item', $total);
                                        })
                                        ->unique(),

                                    TextInput::make('code_103')
                                        ->label('Code 103')
                                        ->placeholder('Otomatis')
                                        ->disabled()
                                        ->dehydrated(true),

                                    TextInput::make('total_item')
                                        ->label('Total Item')
                                        ->placeholder('Otomatis')
                                        ->numeric()
                                        ->disabled()
                                        ->suffix('Item')
                                        ->dehydrated(true),
                                    DatePicker::make('tanggal_kirim')
                                        ->label('Tanggal Kirim')
                                        ->placeholder('Otomatis')
                                        ->native(false)
                                        ->disabled()
                                        ->dehydrated(true),
                                ]),

                                Hidden::make('transmittal_kirim_id')
                                    ->required(),

                                Hidden::make('do_receipt_detail_id')
                                    ->required(),
                            ])
                            ->addActionLabel('Tambah Daftar')
                            ->columnSpanFull()
                            ->addAction(
                                fn(Action $action) => $action
                                    ->label('Tambah Daftar 10')
                                    ->icon('heroicon-o-plus')
                                    ->action(function (callable $get, callable $set) {
                                        $state = $get('transmittalKembaliDetails') ?? [];

                                        for ($i = 0; $i < 10; $i++) {
                                            $state[] = [
                                                'code' => '',
                                                'code_103' => '',
                                                'tanggal_kirim' => '',
                                                'total_item' => '',
                                                'transmittal_kirim_id' => null,
                                                'do_receipt_detail_id' => null,
                                            ];
                                        }

                                        $set('transmittalKembaliDetails', $state);
                                    })
                            )
                            ->addActionAlignment(Alignment::End)
                            ->defaultItems(10),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_kembali')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransmittalKembalis::route('/'),
            'create' => Pages\CreateTransmittalKembali::route('/create'),
            'view' => Pages\ViewTransmittalKembali::route('/{record}'),
            'edit' => Pages\EditTransmittalKembali::route('/{record}/edit'),
        ];
    }
}
