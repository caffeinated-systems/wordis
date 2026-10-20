<?php

declare(strict_types=1);

use App\Http\Controllers\GuessController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Scoring a guess changes nothing, so it skips sessions and CSRF and doesn't wake the database for a session.
Route::post('/guesses', GuessController::class)
    ->withoutMiddleware('web')
    ->middleware('throttle:guesses');
