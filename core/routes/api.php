<?php

use App\Http\Controllers\Api\AskController;
use App\Http\Controllers\Api\PersonalityController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Support\Facades\Route;

// API publique en lecture seule, consommée par le front Next.js.
Route::get('/verifications', [VerificationController::class, 'index']);
Route::get('/categories', [VerificationController::class, 'categories']);
Route::get('/verifications/{slug}', [VerificationController::class, 'show']);

Route::get('/personalities', [PersonalityController::class, 'index']);
Route::get('/personalities/{slug}', [PersonalityController::class, 'show']);

// Endpoints publics en écriture : limités en débit (anti-spam de la file).
Route::middleware('throttle:20,1')->group(function () {
    // Assistant conversationnel (RAG déterministe, garde-fou anti-hallucination).
    Route::post('/ask', [AskController::class, 'ask']);

    // Signalement public d'une rumeur → file éditoriale.
    Route::post('/submissions', [SubmissionController::class, 'store']);
});
