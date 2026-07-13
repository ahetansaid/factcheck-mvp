<?php

use App\Http\Controllers\Api\PersonalityController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Support\Facades\Route;

// API publique en lecture seule, consommée par le front Next.js.
Route::get('/verifications', [VerificationController::class, 'index']);
Route::get('/verifications/{slug}', [VerificationController::class, 'show']);

Route::get('/personalities', [PersonalityController::class, 'index']);
Route::get('/personalities/{slug}', [PersonalityController::class, 'show']);
