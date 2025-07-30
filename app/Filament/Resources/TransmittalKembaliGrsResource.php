<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalGrs;
use App\Filament\Resources\TransmittalKembaliGrsResource\Pages;
use App\Filament\Resources\TransmittalKembaliGrsResource\RelationManagers;
use App\Models\TransmittalKembaliGrs;
use App\Models\TransmittalKirimGrs;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
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

class TransmittalKembaliGrsResource extends Resource
{
    protected static ?string $model = TransmittalKembaliGrs::class;
    protected static ?string $cluster = TransmittalGrs::class;
    protected static ?string $label = 'Kembali';
    protected static ?string $navigationGroup = 'Dokumen Kirim & Kembali';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-down-on-square';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Transmittal Kembali GRS';
    protected static ?string $slug = 'kembali-grs';

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
                                ->default(now())
                                ->native(false)
                                ->required(),

                            Select::make('created_by')
                                ->label('Dibuat oleh')
                                ->relationship('users', 'name')
                                ->native(false)
                                ->disabled()
                                ->dehydrated()
                                ->default(Auth::id())
                                ->required(),
                        ]),
                    ]),

                Section::make('Daftar Transmittal Kembali')
                    ->description('Scan QR Code Transmittal untuk mengisi data pengembalian secara otomatis.')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('transmittalKembaliGrsDetails')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('code')
                                        ->label('Scan QR Code')
                                        ->placeholder('Scan QR Dokumen')
                                        ->autofocus()
                                        ->live()
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $transmittal = TransmittalKirimGrs::where('code', $state)->first();

                                            if (!$transmittal) {
                                                Notification::make()
                                                    ->title("QR Code tidak ditemukan.")
                                                    ->danger()
                                                    ->send();

                                                $set('code_105', null);
                                                $set('total_item', null);
                                                $set('tanggal_kirim', null);
                                                $set('transmittal_kirim_grs_id', null);
                                                $set('do_receipt_detail_id', null);
                                                return;
                                            }

                                            $tanggal = $transmittal->tanggal_kirim instanceof \Carbon\Carbon
                                                ? $transmittal->tanggal_kirim
                                                : \Illuminate\Support\Carbon::parse($transmittal->tanggal_kirim);

                                            $set('code_105', $transmittal->code_105);
                                            $set('tanggal_kirim', $tanggal->format('Y-m-d'));
                                            $set('transmittal_kirim_grs_id', $transmittal->id);
                                            $set('do_receipt_detail_id', optional($transmittal->deliveryOrderReceipt)->id);

                                            $total = $transmittal->deliveryOrderReceipt?->deliveryOrderReceiptDetails->count() ?? 0;
                                            $set('total_item', $total);

                                            $details = $get('../../transmittalKembaliGrsDetails');
                                            if (count($details) >= 1) {
                                                $details[] = [
                                                    'code' => '',
                                                    'code_105' => '',
                                                    'tanggal_kirim' => '',
                                                    'total_item' => '',
                                                    'transmittal_kirim_grs_id' => null,
                                                    'do_receipt_detail_id' => null,
                                                ];

                                                $set('../../transmittalKembaliGrsDetails', $details);
                                            }
                                        }),

                                    TextInput::make('code_105')
                                        ->label('Code 105')
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

                                Hidden::make('transmittal_kirim_grs_id')->required(),
                                Hidden::make('do_receipt_detail_id')->required(),
                            ])
                            ->addActionLabel('Tambah Daftar')
                            ->columnSpanFull()
                            ->addAction(
                                fn(Action $action) => $action
                                    ->label('Tambah Daftar 5')
                                    ->icon('heroicon-o-plus')
                                    ->action(function (callable $get, callable $set) {
                                        $state = $get('transmittalKembaliGrsDetails') ?? [];

                                        for ($i = 0; $i < 5; $i++) {
                                            $state[] = [
                                                'code' => '',
                                                'code_105' => '',
                                                'tanggal_kirim' => '',
                                                'total_item' => '',
                                                'transmittal_kirim_grs_id' => null,
                                                'do_receipt_detail_id' => null,
                                            ];
                                        }

                                        $set('transmittalKembaliGrsDetails', $state);
                                    })
                            )
                            ->addActionAlignment(Alignment::End)
                            ->defaultItems(2),
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
            'index' => Pages\ListTransmittalKembaliGrs::route('/'),
            'create' => Pages\CreateTransmittalKembaliGrs::route('/create'),
            'view' => Pages\ViewTransmittalKembaliGrs::route('/{record}'),
            'edit' => Pages\EditTransmittalKembaliGrs::route('/{record}/edit'),
        ];
    }
}
