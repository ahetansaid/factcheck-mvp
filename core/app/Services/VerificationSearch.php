<?php

namespace App\Services;

use App\Models\Verification;
use Illuminate\Support\Str;

/**
 * Récupération d'une vérification pertinente pour une question.
 *
 * v1 : appariement par mots-clés sur les vérifications publiées (déterministe,
 * aucune dépendance). Remplaçable par une recherche sémantique pgvector (L2 v3)
 * sans changer l'appelant — c'est le point d'extension du RAG.
 */
class VerificationSearch
{
    /** Mots vides ignorés dans l'appariement. */
    private const STOP = [
        'est', 'ce', 'cette', 'cet', 'ces', 'les', 'des', 'une', 'aux', 'que', 'qui',
        'quoi', 'pour', 'par', 'sur', 'dans', 'avec', 'sans', 'plus', 'pas', 'non',
        'oui', 'sont', 'ont', 'fait', 'faire', 'cela', 'ceci', 'vrai', 'faux', 'the',
        'and', 'est-ce', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'mon', 'ton',
        'son', 'notre', 'votre', 'leur', 'the', 'un', 'du', 'de', 'la', 'le',
    ];

    private function normalize(string $s): string
    {
        return Str::ascii(mb_strtolower($s));
    }

    /** @return string[] */
    private function tokens(string $q): array
    {
        $q = $this->normalize($q);
        $words = preg_split('/[^a-z0-9]+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => strlen($w) >= 3 && ! in_array($w, self::STOP, true),
        )));
    }

    /** Meilleure vérification pour la question, ou null si rien de convaincant. */
    public function best(string $question): ?Verification
    {
        $tokens = $this->tokens($question);
        if (empty($tokens)) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach (Verification::published()->with('sources')->get() as $v) {
            $hay = $this->normalize(implode(' ', [$v->title, $v->claim, $v->summary, (string) $v->category]));
            $score = 0;
            foreach ($tokens as $t) {
                if (str_contains($hay, $t)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $v;
            }
        }

        // Exiger un recouvrement minimal (≈ un tiers des mots utiles, au moins 1).
        $need = max(1, (int) ceil(count($tokens) * 0.34));

        return $bestScore >= $need ? $best : null;
    }
}
