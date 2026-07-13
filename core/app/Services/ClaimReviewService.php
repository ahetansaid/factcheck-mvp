<?php

namespace App\Services;

use App\Models\Verification;

/**
 * Produit le JSON-LD schema.org (ClaimReview + Article) d'une vérification.
 *
 * C'est la brique « AI-native » : ce balisage, injecté en SSR par le front,
 * rend la vérification citable par Google, Perplexity, ChatGPT, Claude.
 * Réf. https://schema.org/ClaimReview — validable au Google Rich Results Test.
 */
class ClaimReviewService
{
    /** URL publique (front Next.js) de la page d'une vérification. */
    public function pageUrl(Verification $v): string
    {
        $front = rtrim((string) config('app.front_url'), '/');

        return "{$front}/verifications/{$v->slug}";
    }

    /** Le graphe JSON-LD complet à injecter dans <script type="application/ld+json">. */
    public function graph(Verification $v): array
    {
        $orgName = config('app.name', 'Verifon');
        $front = rtrim((string) config('app.front_url'), '/');
        $url = $this->pageUrl($v);
        $published = optional($v->published_at)->toIso8601String();

        $author = ['@type' => 'Organization', 'name' => $orgName, 'url' => $front];

        $claim = ['@type' => 'Claim', 'text' => $v->claim];
        if ($v->personality) {
            $claim['author'] = ['@type' => 'Person', 'name' => $v->personality->name];
        }

        $claimReview = [
            '@type' => 'ClaimReview',
            'url' => $url,
            'datePublished' => $published,
            'claimReviewed' => $v->claim,
            'author' => $author,
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $v->ratingValue(),
                'bestRating' => 5,
                'worstRating' => 1,
                'alternateName' => $v->ratingLabel(),
            ],
            'itemReviewed' => $claim,
        ];

        $article = [
            '@type' => 'Article',
            'headline' => $v->title,
            'description' => $v->summary,
            'datePublished' => $published,
            'author' => $author,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => [$claimReview, $article],
        ];
    }
}
