<?php

namespace App\Console\Commands;

use App\Models\Verification;
use App\Services\ClaimReviewService;
use Illuminate\Console\Command;

/**
 * Valide le balisage ClaimReview de chaque vérification publiée.
 *
 * Vérifie la présence des champs exigés par schema.org / Google Rich Results
 * (claimReviewed, reviewRating complet, url, author, itemReviewed).
 * Exécutée en CI : le build échoue si un balisage est incomplet.
 */
class ValidateClaimReview extends Command
{
    protected $signature = 'claimreview:validate';

    protected $description = 'Valide le balisage ClaimReview des vérifications publiées.';

    public function handle(ClaimReviewService $service): int
    {
        $verifs = Verification::published()->with(['personality', 'sources'])->get();

        if ($verifs->isEmpty()) {
            $this->warn('Aucune vérification publiée à valider.');

            return self::SUCCESS;
        }

        $invalid = 0;

        foreach ($verifs as $v) {
            $graph = $service->graph($v);
            $claimReview = collect($graph['@graph'] ?? [])->firstWhere('@type', 'ClaimReview');
            $problems = $this->problems($claimReview);

            if ($problems) {
                $invalid++;
                $this->error("✗ {$v->slug}");
                foreach ($problems as $p) {
                    $this->line("      - {$p}");
                }
            } else {
                $this->info("✓ {$v->slug}");
            }
        }

        if ($invalid > 0) {
            $this->newLine();
            $this->error("{$invalid} vérification(s) avec un ClaimReview invalide.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Balisage valide pour les {$verifs->count()} vérifications publiées.");

        return self::SUCCESS;
    }

    /** @return string[] liste des champs manquants (vide si conforme) */
    private function problems(?array $cr): array
    {
        if (! $cr) {
            return ['ClaimReview absent du graphe'];
        }

        $problems = [];
        foreach (['claimReviewed', 'url', 'itemReviewed'] as $field) {
            if (empty($cr[$field])) {
                $problems[] = "{$field} manquant";
            }
        }
        if (empty($cr['author']['name'] ?? null)) {
            $problems[] = 'author.name manquant';
        }

        $rating = $cr['reviewRating'] ?? null;
        if (! $rating) {
            $problems[] = 'reviewRating manquant';
        } else {
            foreach (['ratingValue', 'bestRating', 'worstRating', 'alternateName'] as $k) {
                if (! isset($rating[$k]) || $rating[$k] === '') {
                    $problems[] = "reviewRating.{$k} manquant";
                }
            }
        }

        return $problems;
    }
}
