<?php

namespace App\Filament\Resources\Verifications\Schemas;

use App\Models\Verification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VerificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Identifiant d\'URL')
                    ->helperText('Laissé vide : généré automatiquement depuis le titre.')
                    ->columnSpanFull(),

                Textarea::make('claim')
                    ->label('Affirmation vérifiée')
                    ->helperText('L\'affirmation exacte que l\'on vérifie.')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Select::make('rating')
                    ->label('Verdict')
                    ->options(collect(Verification::RATINGS)->map(fn ($m) => $m['label'])->all())
                    ->required()
                    ->native(false),

                Select::make('personality_id')
                    ->label('Personnalité / source')
                    ->relationship('personality', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Textarea::make('summary')
                    ->label('Résumé du verdict')
                    ->helperText('Le verdict en une à trois phrases, affiché en tête d\'article.')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                RichEditor::make('body')
                    ->label('Article complet')
                    ->columnSpanFull(),

                Repeater::make('sources')
                    ->label('Sources')
                    ->relationship()
                    ->schema([
                        TextInput::make('title')->label('Titre de la source'),
                        TextInput::make('url')->label('Lien')->url()->required(),
                    ])
                    ->addActionLabel('Ajouter une source')
                    ->defaultItems(1)
                    ->columnSpanFull(),

                TextInput::make('category')
                    ->label('Catégorie')
                    ->datalist(['Santé', 'Gouvernance', 'Image', 'Économie', 'Sécurité', 'Société']),

                Select::make('status')
                    ->label('Statut')
                    ->options(['draft' => 'Brouillon', 'published' => 'Publié'])
                    ->default('draft')
                    ->required()
                    ->native(false),

                DateTimePicker::make('published_at')
                    ->label('Date de publication')
                    ->helperText('À renseigner lors de la publication.'),

                Select::make('author_id')
                    ->label('Auteur')
                    ->relationship('author', 'name')
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->preload(),
            ]);
    }
}
