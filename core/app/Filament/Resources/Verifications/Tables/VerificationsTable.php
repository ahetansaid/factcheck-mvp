<?php

namespace App\Filament\Resources\Verifications\Tables;

use App\Models\Verification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('rating')
                    ->label('Verdict')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Verification::RATINGS[$state]['label'] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'true' => 'success',
                        'false' => 'danger',
                        'misleading' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('personality.name')
                    ->label('Personnalité')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'published' ? 'Publié' : 'Brouillon')
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),

                TextColumn::make('published_at')
                    ->label('Publié le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
