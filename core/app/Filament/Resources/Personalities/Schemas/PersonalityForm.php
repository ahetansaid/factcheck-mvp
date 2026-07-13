<?php

namespace App\Filament\Resources\Personalities\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PersonalityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Identifiant d\'URL')
                    ->helperText('Laissé vide : généré depuis le nom.'),

                TextInput::make('role')
                    ->label('Fonction / rôle')
                    ->columnSpanFull(),

                Textarea::make('bio')
                    ->label('Biographie')
                    ->rows(4)
                    ->columnSpanFull(),

                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->directory('personalities')
                    ->columnSpanFull(),
            ]);
    }
}
