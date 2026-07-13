<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Models\Submission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('content')
                    ->label('Affirmation signalée')
                    ->wrap()
                    ->limit(90)
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Origine')
                    ->badge()
                    ->formatStateUsing(fn (string $s) => $s === 'bot' ? 'Bot' : 'Formulaire')
                    ->color(fn (string $s) => $s === 'bot' ? 'info' : 'gray'),

                SelectColumn::make('status')
                    ->label('Statut')
                    ->options(Submission::STATUSES)
                    ->selectablePlaceholder(false),

                TextColumn::make('contact')
                    ->label('Contact')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Reçu')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Submission::STATUSES),
                SelectFilter::make('type')
                    ->label('Origine')
                    ->options(['bot' => 'Bot', 'form' => 'Formulaire']),
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
