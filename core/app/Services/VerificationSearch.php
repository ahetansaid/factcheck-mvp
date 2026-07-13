<?php

namespace App\Services;

use App\Models\Verification;
use Illuminate\Support\Str;

/**
 * Récupération d'une vérification pertinente pour une question.
 *
 * Stratégie : recherche SÉMANTIQUE d'abord (embeddings e5 + cosinus), avec repli
 * sur l'appariement par MOTS-CLÉS si le worker d'embeddings est indisponible ou
 * si aucune vérification n'est encore vectorisée. Déterministe et sans invention.
 */
class VerificationSearch
{
    private const STOP = [
        'est', 'ce', 'cette', 'cet', 'ces', 'les', 'des', 'une', 'aux', 'que', 'qui',
        'quoi', 'pour', 'par', 'sur', 'dans', 'avec', 'sans', 'plus', 'pas', 'non',
        'oui', 'sont', 'ont', 'fait', 'faire', 'cela', 'ceci', 'vrai', 'faux', 'the',
        'and', 'est-ce', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'mon', 'ton',
        'son', 'notre', 'votre', 'leur', 'un', 'du', 'de', 'la', 'le',
    ];

    public function __construct(private EmbeddingClient $embeddings) {}

    /** Meilleure vérification pour la question, ou null si rien de convaincant. */
    public function best(string $question): ?Verification
    {
        return $this->semanticBest($question) ?? $this->keywordBest($question);
    }

    // --- Sémantique -------------------------------------------------------
    private function semanticBest(string $question): ?Verification
    {
        $qv = $this->embeddings->embedOne($question, 'query');
        if (! $qv) {
            return null; // worker indisponible → repli mots-clés
        }

        $verifs = Verification::published()->whereNotNull('embedding')->with('sources')->get();
        if ($verifs->isEmpty()) {
            return null;
        }

        $best = null;
        $bestSim = -1.0;
        foreach ($verifs as $v) {
            $vec = $v->embedding;
            if (! is_array($vec)) {
                continue;
            }
            $sim = $this->cosine($qv, $vec);
            if ($sim > $bestSim) {
                $bestSim = $sim;
                $best = $v;
            }
        }

        return $bestSim >= (float) config('services.embeddings.threshold') ? $best : null;
    }

    /** Produit scalaire — les vecteurs du worker sont normalisés, donc = cosinus. */
    private function cosine(array $a, array $b): float
    {
        $sum = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $sum += ((float) $a[$i]) * ((float) $b[$i]);
        }

        return $sum;
    }

    // --- Mots-clés (repli) ------------------------------------------------
    private function keywordBest(string $question): ?Verification
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

        $need = max(1, (int) ceil(count($tokens) * 0.34));

        return $bestScore >= $need ? $best : null;
    }

    private function normalize(string $s): string
    {
        return Str::ascii(mb_strtolower($s));
    }

    /** @return string[] */
    private function tokens(string $q): array
    {
        $words = preg_split('/[^a-z0-9]+/', $this->normalize($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => strlen($w) >= 3 && ! in_array($w, self::STOP, true),
        )));
    }
}
