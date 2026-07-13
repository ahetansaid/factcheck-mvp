<?php

namespace App\Filament\Resources\Personalities;

use App\Filament\Resources\Personalities\Pages\CreatePersonality;
use App\Filament\Resources\Personalities\Pages\EditPersonality;
use App\Filament\Resources\Personalities\Pages\ListPersonalities;
use App\Filament\Resources\Personalities\Schemas\PersonalityForm;
use App\Filament\Resources\Personalities\Tables\PersonalitiesTable;
use App\Models\Personality;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PersonalityResource extends Resource
{
    protected static ?string $model = Personality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PersonalityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalitiesTable::configure($table);
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
            'index' => ListPersonalities::route('/'),
            'create' => CreatePersonality::route('/create'),
            'edit' => EditPersonality::route('/{record}/edit'),
        ];
    }
}
