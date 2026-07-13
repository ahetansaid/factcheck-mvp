<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personality;
use App\Models\Verification;

class PersonalityController extends Controller
{
    /** Annuaire des personnalités avec compteurs Vrai/Faux (vérifs publiées). */
    public function index()
    {
        return Personality::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Personality $p) => $this->summary($p));
    }

    /** Détail d'une personnalité + ses vérifications publiées. */
    public function show(string $slug)
    {
        $p = Personality::where('slug', $slug)->firstOrFail();

        return $this->summary($p) + [
            'bio' => $p->bio,
            'verifications' => $p->verifications()
                ->published()
                ->latest('published_at')
                ->get()
                ->map(fn (Verification $v) => [
                    'title' => $v->title,
                    'slug' => $v->slug,
                    'rating' => $v->rating,
                    'rating_label' => $v->ratingLabel(),
                    'summary' => $v->summary,
                    'published_at' => optional($v->published_at)->toIso8601String(),
                ]),
        ];
    }

    private function summary(Personality $p): array
    {
        $counts = [];
        foreach (array_keys(Verification::RATINGS) as $r) {
            $counts[$r] = $p->statCount($r);
        }

        return [
            'name' => $p->name,
            'slug' => $p->slug,
            'role' => $p->role,
            'counts' => $counts,
            'total' => array_sum($counts),
        ];
    }
}
