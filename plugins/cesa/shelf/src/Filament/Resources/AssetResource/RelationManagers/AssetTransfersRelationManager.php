<?php

namespace Cesa\Shelf\Filament\Resources\AssetResource\RelationManagers;

use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetTransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'assetTransferDetails';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('assetTransfer.letter_number'),
                TextInput::make('assetTransfer.fromUser.name'),
                TextInput::make('assetTransfer.toUser.name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('letter_number')
            ->columns([
                TextColumn::make('assetTransfer.letter_number')
                    ->translateLabel()
                    ->badge(),
                TextColumn::make('assetTransfer.fromUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('assetTransfer.toUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('success'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary'   => 'BERITA ACARA SERAH TERIMA',
                        'success'   => 'BERITA ACARA PENGALIHAN BARANG',
                        'danger'    => 'BERITA ACARA PENGEMBALIAN BARANG',
                        'secondary' => 'Unknown Status',
                    ])
                    ->getStateUsing(fn ($record): string => $record->assetTransfer?->status ?? 'Unknown Status'),
                TextColumn::make('assetTransfer.document')
                    ->url(fn ($record) => $record && $record->assetTransfer ? $record->assetTransfer->managedFileUrl('document') : null, true)
                    ->openUrlInNewTab()
                    ->translateLabel()
                    ->getStateUsing(fn ($record) => $record->assetTransfer && $record->assetTransfer->document ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text'),
                TextColumn::make('assetTransfer.created_at')
                    ->date()
                    ->label(__('Created at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([
                \Filament\Actions\Action::make('createAssetTransfer')
                    ->label('Transfer Asset')
                    ->url(AssetTransferResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->color('success'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
