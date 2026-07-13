<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\AnswerComposer;
use App\Services\VerificationSearch;
use Illuminate\Http\Request;

/**
 * Assistant conversationnel — « c'est vrai, ça ? ».
 *
 * Garde-fou dur : la réponse provient UNIQUEMENT d'une vérification publiée,
 * avec sa source. Aucune correspondance → on ne devine pas, on transmet à la
 * rédaction. Le modèle ne produit jamais un verdict absent de la base.
 */
class AskController extends Controller
{
    public function ask(Request $request, VerificationSearch $search, AnswerComposer $composer)
    {
        $question = trim((string) $request->input('question', ''));

        if (mb_strlen($question) < 3) {
            return response()->json([
                'matched' => false,
                'message' => 'Posez une question sur une affirmation à vérifier.',
            ]);
        }

        $v = $search->best($question);

        if (! $v) {
            // Garde-fou : aucune réponse en base → on crée réellement une entrée
            // dans la file éditoriale (pas de doublon exact récent).
            Submission::firstOrCreate(
                ['type' => 'bot', 'content' => $question, 'status' => 'new'],
            );

            return response()->json([
                'matched' => false,
                'message' => "Cette affirmation n'a pas encore été vérifiée par notre rédaction. "
                    . "Nous l'avons transmise à l'équipe éditoriale.",
            ]);
        }

        // Claude reformule en langage naturel ; repli déterministe si indisponible.
        $message = $composer->compose($question, $v)
            ?? "Verdict : {$v->ratingLabel()}. {$v->summary}";

        return response()->json([
            'matched' => true,
            'message' => $message,
            'verification' => [
                'title' => $v->title,
                'slug' => $v->slug,
                'rating' => $v->rating,
                'rating_label' => $v->ratingLabel(),
                'summary' => $v->summary,
                'sources' => $v->sources->map(fn ($s) => ['title' => $s->title, 'url' => $s->url])->all(),
            ],
        ]);
    }
}
