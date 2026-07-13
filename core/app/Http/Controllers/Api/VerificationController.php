<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationResource;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /** Liste paginée des vérifications publiées (les plus récentes d'abord). */
    public function index(Request $request)
    {
        $items = Verification::published()
            ->with('personality')
            ->latest('published_at')
            ->paginate(12);

        $items->through(fn (Verification $v) => [
            'title' => $v->title,
            'slug' => $v->slug,
            'claim' => $v->claim,
            'rating' => $v->rating,
            'rating_label' => $v->ratingLabel(),
            'summary' => $v->summary,
            'category' => $v->category,
            'personality' => $v->personality?->name,
            'published_at' => optional($v->published_at)->toIso8601String(),
        ]);

        return $items;
    }

    /** Détail d'une vérification publiée (avec ClaimReview). */
    public function show(string $slug)
    {
        $v = Verification::published()
            ->with(['personality', 'sources'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new VerificationResource($v);
    }
}
