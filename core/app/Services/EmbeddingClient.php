<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client du worker d'embeddings (FastAPI, modèle e5-small).
 * Vectorise textes et requêtes pour la recherche sémantique.
 * Retourne null en cas d'indisponibilité (l'appelant retombe sur les mots-clés).
 */
class EmbeddingClient
{
    /**
     * @param  string[]  $texts
     * @param  'query'|'passage'  $kind
     * @return array<int, float[]>|null
     */
    public function embed(array $texts, string $kind = 'query'): ?array
    {
        if (empty($texts)) {
            return [];
        }

        $url = rtrim((string) config('services.embeddings.url'), '/');
        if ($url === '') {
            return null; // worker désactivé (ex. en test) → repli mots-clés
        }

        try {
            $resp = Http::timeout(20)
                ->post($url . '/embed', [
                    'texts' => array_values($texts),
                    'kind' => $kind,
                ]);

            if (! $resp->successful()) {
                Log::warning('Embeddings non-2xx', ['status' => $resp->status()]);

                return null;
            }

            return $resp->json('vectors');
        } catch (\Throwable $e) {
            Log::warning('Embeddings worker indisponible', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /** Vecteur unique pour une requête. */
    public function embedOne(string $text, string $kind = 'query'): ?array
    {
        $vectors = $this->embed([$text], $kind);

        return $vectors[0] ?? null;
    }
}
