<?php

namespace App\Filament\Resources\Personalities\Pages;

use App\Filament\Resources\Personalities\PersonalityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonality extends EditRecord
{
    protected static string $resource = PersonalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
