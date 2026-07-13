<?php

namespace App\Filament\Resources\Personalities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PersonalityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('role'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('photo_path'),
            ]);
    }
}
