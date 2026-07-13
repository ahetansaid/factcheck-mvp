<?php

namespace App\Filament\Widgets;

use App\Models\Personality;
use App\Models\Submission;
use App\Models\Verification;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsRedaction extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $pending = Submission::whereIn('status', ['new', 'reviewing'])->count();

        return [
            Stat::make('Vérifications publiées', Verification::published()->count())
                ->color('success'),
            Stat::make('Brouillons', Verification::where('status', 'draft')->count())
                ->color('gray'),
            Stat::make('File éditoriale', $pending)
                ->description('signalements à traiter')
                ->color($pending > 0 ? 'warning' : 'gray'),
            Stat::make('Personnalités', Personality::count())
                ->color('gray'),
        ];
    }
}
