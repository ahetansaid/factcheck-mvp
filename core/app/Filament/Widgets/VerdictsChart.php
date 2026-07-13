<?php

namespace App\Filament\Widgets;

use App\Models\Verification;
use Filament\Widgets\ChartWidget;

class VerdictsChart extends ChartWidget
{
    protected ?string $heading = 'Répartition des verdicts';

    protected function getData(): array
    {
        $labels = [];
        $data = [];
        $colors = ['false' => '#e2604a', 'misleading' => '#f4ac33', 'unproven' => '#8a9a94', 'true' => '#4bb87c'];

        foreach (Verification::RATINGS as $key => $meta) {
            $labels[] = $meta['label'];
            $data[] = Verification::published()->where('rating', $key)->count();
        }

        return [
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => array_values($colors),
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
