<?php

namespace App\Filament\Resources\Submissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required()
                    ->default('form'),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('contact'),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                Select::make('verification_id')
                    ->relationship('verification', 'title'),
            ]);
    }
}
