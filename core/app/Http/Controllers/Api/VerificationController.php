<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationResource;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /** Liste paginée des vérifications publiées, avec recherche (q) et filtre (category). */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = Verification::published()->with('personality');

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($q !== '') {
            $like = '%' . mb_strtolower($q) . '%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw('lower(title) like ?', [$like])
                    ->orWhereRaw('lower(claim) like ?', [$like])
                    ->orWhereRaw('lower(summary) like ?', [$like]);
            });
        }

        $items = $query->latest('published_at')->paginate(12)->withQueryString();

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

    /** Catégories distinctes des vérifications publiées (pour les filtres). */
    public function categories()
    {
        return Verification::published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();
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
