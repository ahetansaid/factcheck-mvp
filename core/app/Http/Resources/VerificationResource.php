<?php

namespace App\Http\Resources;

use App\Services\ClaimReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation JSON complète d'une vérification (page de détail).
 * Inclut le graphe JSON-LD ClaimReview que le front injecte en SSR.
 */
class VerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'claim' => $this->claim,
            'rating' => $this->rating,
            'rating_label' => $this->ratingLabel(),
            'rating_value' => $this->ratingValue(),
            'summary' => $this->summary,
            'body' => $this->body,
            'category' => $this->category,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'personality' => $this->whenLoaded('personality', fn () => $this->personality ? [
                'name' => $this->personality->name,
                'slug' => $this->personality->slug,
                'role' => $this->personality->role,
            ] : null),
            'sources' => $this->whenLoaded('sources', fn () => $this->sources->map(fn ($s) => [
                'title' => $s->title,
                'url' => $s->url,
            ])),
            'claim_review' => app(ClaimReviewService::class)->graph($this->resource),
        ];
    }
}
