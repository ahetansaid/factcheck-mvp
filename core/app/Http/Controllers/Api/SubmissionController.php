<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

/**
 * Réception des signalements publics (« Signaler une rumeur »).
 * Alimente la même file éditoriale que le garde-fou du bot.
 */
class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);

        Submission::create([
            'type' => 'form',
            'content' => $data['content'],
            'contact' => $data['contact'] ?? null,
            'status' => 'new',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Merci. Votre signalement a été transmis à la rédaction.',
        ], 201);
    }
}
