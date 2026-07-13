<?php

namespace App\Filament\Resources\Personalities\Pages;

use App\Filament\Resources\Personalities\PersonalityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalities extends ListRecords
{
    protected static string $resource = PersonalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
