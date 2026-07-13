<?php

namespace App\Filament\Resources\Verifications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VerificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('claim')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('rating')
                    ->required(),
                Textarea::make('summary')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->columnSpanFull(),
                TextInput::make('category'),
                Select::make('personality_id')
                    ->relationship('personality', 'name'),
                Select::make('author_id')
                    ->relationship('author', 'name'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at'),
            ]);
    }
}
