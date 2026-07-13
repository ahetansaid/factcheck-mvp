<?php

namespace App\Console\Commands;

use App\Models\Verification;
use App\Services\EmbeddingClient;
use Illuminate\Console\Command;

/**
 * Calcule et stocke les embeddings des vérifications publiées (via le worker).
 * À relancer après publication de nouvelles vérifications.
 */
class BuildEmbeddings extends Command
{
    protected $signature = 'embeddings:build {--all : recalculer même celles déjà vectorisées}';

    protected $description = 'Calcule et stocke les embeddings des vérifications publiées.';

    public function handle(EmbeddingClient $client): int
    {
        $query = Verification::published();
        if (! $this->option('all')) {
            $query->whereNull('embedding');
        }

        $verifs = $query->get()->values();

        if ($verifs->isEmpty()) {
            $this->info('Aucune vérification à vectoriser.');

            return self::SUCCESS;
        }

        $texts = $verifs->map(fn (Verification $v) => $v->embeddingText())->all();
        $vectors = $client->embed($texts, 'passage');

        if (! $vectors || count($vectors) !== $verifs->count()) {
            $this->error('Worker d\'embeddings indisponible (démarrer le service sur le port 8100).');

            return self::FAILURE;
        }

        foreach ($verifs as $i => $v) {
            $v->embedding = $vectors[$i];
            $v->saveQuietly();
        }

        $this->info("{$verifs->count()} vérification(s) vectorisée(s).");

        return self::SUCCESS;
    }
}
